<?php

namespace App\Services;

use App\Models\Page;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Dedicated service name for Facebook feed sync flows.
 *
 * Single-page feed + post-insights sync implementation.
 */
class FacebookFeedSyncService
{
    protected FacebookService $facebookService;

    protected array $durations = ['full_year'];

    protected array $cacheDurations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year', 'full_year'];

    protected int $postsLimit = 100;

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
     * Sync Facebook feed and post insights for one page + duration.
     */
    public function syncPagePosts(Page $page, string $duration): array
    {
        $result = $this->fetchSinglePageFeedWithInsights($page, $duration);
        if (! $result['success']) {
            return [];
        }

        return $result['posts'];
    }

    /**
     * Fetch feed posts and post-level insights for a single page.
     *
     * @return array{
     *   success: bool,
     *   posts: array,
     *   since: string,
     *   until: string
     * }
     */
    protected function fetchSinglePageFeedWithInsights(Page $page, string $duration): array
    {
        $until = Carbon::today()->format('Y-m-d');
        $since = null;

        if (empty($page->page_id) || empty($page->access_token)) {
            return [
                'success' => false,
                'posts' => [],
                'since' => $since,
                'until' => $until,
            ];
        }

        $tokenCheck = FacebookService::validateToken($page);
        if (! $tokenCheck['success']) {
            return [
                'success' => false,
                'posts' => [],
                'since' => $since,
                'until' => $until,
            ];
        }

        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;
        $insightsPreset = $duration === 'full_year' ? 'sent_tab' : 'default';
        $posts = [];
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $posts = $this->facebookService->getPagePostsWithInsights(
                (string) $page->page_id,
                (string) $accessToken,
                null,
                $until,
                $insightsPreset
            );

            if (! empty($posts)) {
                break;
            }

            if ($attempt < $maxAttempts) {
                $this->throttleEmptyFeedRetry();
            }
        }

        $posts = $this->limitPostsNewestFirst(
            $this->normalizePostCreatedDate(is_array($posts) ? $posts : []),
            $this->postsLimit
        );

        return [
            'success' => true,
            'posts' => is_array($posts) ? $posts : [],
            'since' => $since,
            'until' => $until,
        ];
    }

    private function throttleEmptyFeedRetry(): void
    {
        $delayMs = (int) env('FACEBOOK_FEED_EMPTY_RETRY_DELAY_MS', 1200);
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }

    /**
     * Sync all unique Facebook pages from pages table across all durations.
     * Uniqueness is by external facebook page_id (shared page across users).
     */
    public function syncAll(?callable $progress = null): array
    {
        $pages = $this->fetchAllUniquePagesFromPagesTable();

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
                    'external_page_id' => $page->page_id ?? null,
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
                    $posts = $this->syncPagePosts($page, $duration);
                    if (! empty($posts)) {
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
                    Log::warning('Facebook feed sync failed', [
                        'page_id' => $page->id,
                        'external_page_id' => $page->page_id,
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

                $delayMs = (int) env('PAGE_POSTS_SYNC_STEP_DELAY_MS', 500);
                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }

            foreach ($this->cacheDurations as $cacheDuration) {
                [$since, $until] = $this->resolveDateRange($cacheDuration);
                $this->storeDurationPostsCaches($page, $cacheDuration, $since, $until, $posts ?? []);
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
     * Get unique pages from pages table by external facebook page_id.
     */
    protected function fetchAllUniquePagesFromPagesTable()
    {
        $rows = Page::withoutGlobalScopes()
            ->whereNotNull('page_id')
            ->whereNotNull('access_token')
            ->where('page_id', '!=', '')
            ->where('access_token', '!=', '')
            ->orderByDesc('id')
            ->get();

        return $rows->groupBy('page_id')
            ->map(function ($pages) {
                foreach ($pages as $page) {
                    $tokenCheck = FacebookService::validateToken($page);
                    if ($tokenCheck['success']) {
                        $page->access_token = $tokenCheck['access_token'] ?? $page->access_token;

                        return $page;
                    }
                }

                return $pages->first();
            })
            ->values();
    }

    /**
     * Store duration-filtered posts cache (shared by external page_id).
     */
    protected function storeDurationPostsCaches(Page $page, string $duration, string $since, string $until, array $posts): void
    {
        if (empty($page->page_id)) {
            return;
        }

        $posts = $this->limitPostsNewestFirst(
            $this->filterPostsByDateRange(
                $this->normalizePostCreatedDate($posts),
                $since,
                $until
            ),
            $this->postsLimit
        );

        $cacheKey = $this->facebookDurationPostsCacheKey((string) $page->page_id, $duration, $since, $until);
        Cache::put($cacheKey, $posts, now()->addHours(24));

        $payload = [
            'page_id' => (string) $page->page_id,
            'duration' => $duration,
            'since' => $since,
            'until' => $until,
            'updated_at' => now()->toIso8601String(),
            'posts' => $posts,
        ];

        Storage::disk('local')->put(
            $this->facebookDurationPostsCachePath((string) $page->page_id, $duration, $since, $until),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Ensure each post has post_created_date for downstream usage.
     */
    protected function filterPostsByDateRange(array $posts, string $since, string $until): array
    {
        $fromTs = strtotime($since.' 00:00:00 UTC') ?: 0;
        $toTs = strtotime($until.' 23:59:59 UTC') ?: PHP_INT_MAX;

        return array_values(array_filter($posts, function ($post) use ($fromTs, $toTs) {
            if (! is_array($post)) {
                return false;
            }

            $createdRaw = $post['post_created_date'] ?? $post['created_time'] ?? null;
            if (is_array($createdRaw) && isset($createdRaw['date'])) {
                $createdRaw = $createdRaw['date'];
            }
            if (is_object($createdRaw) && method_exists($createdRaw, 'getTimestamp')) {
                $ts = (int) $createdRaw->getTimestamp();
            } else {
                $ts = is_string($createdRaw) ? strtotime($createdRaw) : false;
                $ts = $ts !== false ? $ts : null;
            }

            if ($ts === null) {
                return false;
            }

            return $ts >= $fromTs && $ts <= $toTs;
        }));
    }

    protected function limitPostsNewestFirst(array $posts, int $limit): array
    {
        usort($posts, function ($a, $b) {
            $ta = $this->postSortTimestamp($a);
            $tb = $this->postSortTimestamp($b);

            return $tb <=> $ta;
        });

        return array_slice($posts, 0, $limit);
    }

    /**
     * @param  mixed  $post
     */
    protected function postSortTimestamp($post): int
    {
        if (! is_array($post)) {
            return 0;
        }

        $createdRaw = $post['post_created_date'] ?? $post['created_time'] ?? null;
        if (is_array($createdRaw) && isset($createdRaw['date'])) {
            $createdRaw = $createdRaw['date'];
        }
        if (is_object($createdRaw) && method_exists($createdRaw, 'getTimestamp')) {
            return (int) $createdRaw->getTimestamp();
        }

        if (! is_string($createdRaw)) {
            return 0;
        }

        $ts = strtotime($createdRaw);

        return $ts !== false ? $ts : 0;
    }

    protected function normalizePostCreatedDate(array $posts): array
    {
        return array_map(function ($post) {
            if (! is_array($post)) {
                return $post;
            }

            if (! empty($post['post_created_date'])) {
                return $post;
            }

            $createdRaw = $post['created_time'] ?? null;
            if (is_array($createdRaw)) {
                $createdRaw = $createdRaw['date'] ?? null;
            }
            if (is_object($createdRaw) && method_exists($createdRaw, 'format')) {
                $createdRaw = $createdRaw->format('Y-m-d H:i:s');
            }

            if (is_string($createdRaw) && $createdRaw !== '') {
                $ts = strtotime($createdRaw);
                if ($ts !== false) {
                    $post['post_created_date'] = date('Y-m-d H:i:s', $ts);
                }
            }

            return $post;
        }, $posts);
    }

    protected function facebookDurationPostsCacheKey(string $pageId, string $duration, string $since, string $until): string
    {
        return implode(':', [
            'facebook_posts_by_duration',
            'v1',
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            $since,
            'until',
            $until,
        ]);
    }

    protected function facebookDurationPostsCachePath(string $pageId, string $duration, string $since, string $until): string
    {
        return implode('/', [
            'facebook-posts-cache',
            'durations',
            'page-' . $pageId,
            $duration . '-' . $since . '-' . $until . '.json',
        ]);
    }
}
