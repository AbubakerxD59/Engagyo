<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageInsight;
use App\Models\PagePost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PagePostsSyncService
{
    private const POSTS_CACHE_TTL_HOURS = 3;

    protected FacebookService $facebookService;

    protected array $durations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year', 'full_year'];

    /** Keep only this many records per (page_id, duration); oldest beyond this are removed. */
    protected int $maxRecordsPerDuration = 7;

    private function throttleBetweenSyncSteps(): void
    {
        $delayMs = (int) env('PAGE_POSTS_SYNC_STEP_DELAY_MS', 500);
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }

    public function __construct(FacebookService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    /**
     * Resolve date range from duration based on current date.
     *
     * @return array{0: string, 1: string} [since, until] as Y-m-d
     */
    public function resolveDateRange(string $duration): array
    {
        $today = Carbon::today();

        return match ($duration) {
            'last_7' => [$today->copy()->subDays(7)->format('Y-m-d'), $today->format('Y-m-d')],
            'last_28' => [$today->copy()->subDays(28)->format('Y-m-d'), $today->format('Y-m-d')],
            'last_90' => [$today->copy()->subDays(90)->format('Y-m-d'), $today->format('Y-m-d')],
            'this_month' => [
                $today->copy()->startOfMonth()->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            'this_year' => [
                $today->copy()->startOfYear()->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            'full_year' => [
                $today->copy()->subYear()->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            default => [$today->copy()->subDays(28)->format('Y-m-d'), $today->format('Y-m-d')],
        };
    }

    /**
     * Sync page posts with insights for a single page and duration.
     */
    public function syncPagePosts(Page $page, string $duration): bool
    {
        if (empty($page->page_id) || empty($page->access_token)) {
            return false;
        }

        $tokenCheck = FacebookService::validateToken($page);
        if (!$tokenCheck['success']) {
            return false;
        }

        [$since, $until] = $this->resolveDateRange($duration);
        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;
        $insightsPreset = $duration === 'full_year' ? 'sent_tab' : 'default';
        $posts = $this->facebookService->getPagePostsWithInsights(
            $page->page_id,
            $accessToken,
            $since,
            $until,
            $insightsPreset
        );

        PagePost::updateOrCreate(
            [
                'page_id' => $page->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'posts' => $posts,
                'synced_at' => now(),
            ]
        );

        $this->refreshPostsCaches($page, $duration, $since, $until, $posts);
        $this->prunePagePosts($page->id, $duration);

        return true;
    }

    /**
     * Refresh sent/analytics posts cache for the given user-page-duration window.
     */
    protected function refreshPostsCaches(Page $page, string $duration, string $since, string $until, array $posts): void
    {
        $userId = (int) ($page->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $ttl = now()->addHours(self::POSTS_CACHE_TTL_HOURS);

        $analyticsKey = $this->analyticsPostsCacheKey($userId, (int) $page->id, $duration, $since, $until);
        Cache::forget($analyticsKey);
        Cache::put($analyticsKey, $posts, $ttl);

        if ($duration === 'full_year') {
            $sentKey = $this->sentPostsCacheKey($userId, (int) $page->id, $duration, $since, $until);
            Cache::forget($sentKey);
            Cache::put($sentKey, $posts, $ttl);
        }
    }

    protected function analyticsPostsCacheKey(int $userId, int $pageId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'analytics_posts',
            'v1',
            'user',
            $userId,
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            (string) ($since ?? ''),
            'until',
            (string) ($until ?? ''),
        ]);
    }

    protected function sentPostsCacheKey(int $userId, int $pageId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'schedule_sent_posts',
            'v1',
            'user',
            $userId,
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            (string) ($since ?? ''),
            'until',
            (string) ($until ?? ''),
        ]);
    }

    /**
     * Keep only the latest maxRecordsPerDuration records per (page_id, duration); delete the rest.
     */
    protected function prunePagePosts(int $pageId, string $duration): void
    {
        $idsToKeep = PagePost::where('page_id', $pageId)
            ->where('duration', $duration)
            ->orderByDesc('synced_at')
            ->limit($this->maxRecordsPerDuration)
            ->pluck('id');

        PagePost::where('page_id', $pageId)
            ->where('duration', $duration)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }

    /**
     * Sync page posts with insights for all pages (from page_insight) and all durations.
     */
    public function syncAll(?callable $progress = null): array
    {
        $pageIds = PageInsight::distinct()->pluck('page_id');
        $pages = Page::withoutGlobalScopes()
            ->whereIn('id', $pageIds)
            ->whereNotNull('page_id')
            ->whereNotNull('access_token')
            ->where('page_id', '!=', '')
            ->where('access_token', '!=', '')
            ->get();

        $synced = 0;
        $failed = 0;
        $totalPages = $pages->count();
        $totalDurations = count($this->durations);
        $totalSteps = $totalPages * $totalDurations;
        $step = 0;

        if ($progress) {
            $progress([
                'type' => 'start',
                'total_pages' => $totalPages,
                'total_durations' => $totalDurations,
                'total_steps' => $totalSteps,
            ]);
        }

        foreach ($pages as $page) {
            if ($progress) {
                $progress([
                    'type' => 'page_start',
                    'page_id' => $page->id,
                    'page_name' => $page->name ?? null,
                ]);
            }

            foreach ($this->durations as $duration) {
                $step++;
                [$since, $until] = $this->resolveDateRange($duration);
                if ($progress) {
                    $progress([
                        'type' => 'duration_start',
                        'step' => $step,
                        'total_steps' => $totalSteps,
                        'page_id' => $page->id,
                        'page_name' => $page->name ?? null,
                        'duration' => $duration,
                        'since' => $since,
                        'until' => $until,
                    ]);
                }

                try {
                    if ($this->syncPagePosts($page, $duration)) {
                        $synced++;
                        if ($progress) {
                            $progress([
                                'type' => 'duration_success',
                                'step' => $step,
                                'total_steps' => $totalSteps,
                                'page_id' => $page->id,
                                'page_name' => $page->name ?? null,
                                'duration' => $duration,
                            ]);
                        }
                    } else {
                        $failed++;
                        if ($progress) {
                            $progress([
                                'type' => 'duration_failed',
                                'step' => $step,
                                'total_steps' => $totalSteps,
                                'page_id' => $page->id,
                                'page_name' => $page->name ?? null,
                                'duration' => $duration,
                                'error' => 'Skipped due to missing credentials or invalid token.',
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Page posts sync failed', [
                        'page_id' => $page->id,
                        'duration' => $duration,
                        'error' => $e->getMessage(),
                    ]);
                    if ($progress) {
                        $progress([
                            'type' => 'duration_failed',
                            'step' => $step,
                            'total_steps' => $totalSteps,
                            'page_id' => $page->id,
                            'page_name' => $page->name ?? null,
                            'duration' => $duration,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $this->throttleBetweenSyncSteps();
            }
        }

        if ($progress) {
            $progress([
                'type' => 'done',
                'synced' => $synced,
                'failed' => $failed,
            ]);
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * Sync page posts with insights for a single page across all durations.
     *
     * @return array{success: bool, synced: int, failed: int}
     */
    public function syncPageForAllDurations(Page $page): array
    {
        $synced = 0;
        $failed = 0;

        foreach ($this->durations as $duration) {
            try {
                if ($this->syncPagePosts($page, $duration)) {
                    $synced++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Page posts sync failed', [
                    'page_id' => $page->id,
                    'duration' => $duration,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->throttleBetweenSyncSteps();
        }

        return [
            'success' => $failed === 0,
            'synced' => $synced,
            'failed' => $failed,
        ];
    }

    /**
     * Sync page posts with insights for a single page for full_year duration only.
     * Used when user clicks "Refresh" for the selected account (Sent tab).
     *
     * @return array{success: bool, synced: int, failed: int}
     */
    public function syncPageForFullYear(Page $page): array
    {
        $synced = 0;
        $failed = 0;

        try {
            if ($this->syncPagePosts($page, 'full_year')) {
                $synced = 1;
            } else {
                $failed = 1;
            }
        } catch (\Throwable $e) {
            $failed = 1;
            Log::warning('Page posts sync failed', [
                'page_id' => $page->id,
                'duration' => 'full_year',
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'success' => $failed === 0,
            'synced' => $synced,
            'failed' => $failed,
        ];
    }
}
