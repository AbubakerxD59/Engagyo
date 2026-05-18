<?php

namespace App\Services;

use App\Models\InstagramAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InstagramMediaSyncService
{
    protected array $durations = ['full_year'];

    protected array $cacheDurations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year', 'full_year'];

    protected int $fullYearMediaLimit = 150;

    public function __construct(protected InstagramAnalyticsService $instagramAnalyticsService) {}

    /**
     * @return array{0: string, 1: string}
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
     * @return array<int, array<string, mixed>>
     */
    public function syncAccountMedia(InstagramAccount $account, string $duration): array
    {
        $result = $this->fetchMediaWithInsights($account, $duration);

        return $result['success'] ? $result['posts'] : [];
    }

    /**
     * @return array{success: bool, posts: array<int, array<string, mixed>>, since: string, until: string}
     */
    protected function fetchMediaWithInsights(InstagramAccount $account, string $duration): array
    {
        [$since, $until] = $this->resolveDateRange($duration);

        if (empty($account->ig_user_id) || empty($account->access_token)) {
            return [
                'success' => false,
                'posts' => [],
                'since' => $since,
                'until' => $until,
            ];
        }

        $tokenCheck = FacebookService::validateToken($account);
        if (! ($tokenCheck['success'] ?? false)) {
            return [
                'success' => false,
                'posts' => [],
                'since' => $since,
                'until' => $until,
            ];
        }

        $posts = $this->instagramAnalyticsService->getMediaWithInsights(
            $account,
            $since,
            $until,
            $this->fullYearMediaLimit
        );

        return [
            'success' => true,
            'posts' => is_array($posts) ? $posts : [],
            'since' => $since,
            'until' => $until,
        ];
    }

    /**
     * @return array{synced: int, failed: int}
     */
    public function syncAll(?callable $progress = null): array
    {
        $accounts = InstagramAccount::query()
            ->whereNotNull('ig_user_id')
            ->where('ig_user_id', '!=', '')
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '')
            ->get();

        $synced = 0;
        $failed = 0;
        $totalSteps = $accounts->count() * count($this->durations);
        $step = 0;

        if ($progress) {
            $progress([
                'type' => 'start',
                'total_accounts' => $accounts->count(),
                'total_steps' => $totalSteps,
            ]);
        }

        foreach ($accounts as $account) {
            if ($progress) {
                $progress([
                    'type' => 'account_start',
                    'instagram_account_id' => $account->id,
                    'username' => $account->username,
                ]);
            }

            $posts = [];

            foreach ($this->durations as $duration) {
                $step++;
                [$since, $until] = $this->resolveDateRange($duration);

                if ($progress) {
                    $progress([
                        'type' => 'duration_start',
                        'step' => $step,
                        'total_steps' => $totalSteps,
                        'instagram_account_id' => $account->id,
                        'duration' => $duration,
                        'since' => $since,
                        'until' => $until,
                    ]);
                }

                try {
                    $posts = $this->syncAccountMedia($account, $duration);
                    if ($posts !== []) {
                        $synced++;
                        if ($progress) {
                            $progress([
                                'type' => 'duration_success',
                                'step' => $step,
                                'total_steps' => $totalSteps,
                                'instagram_account_id' => $account->id,
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
                                'instagram_account_id' => $account->id,
                                'duration' => $duration,
                                'error' => 'No media returned or invalid token.',
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Instagram media sync failed', [
                        'instagram_account_id' => $account->id,
                        'duration' => $duration,
                        'error' => $e->getMessage(),
                    ]);
                    if ($progress) {
                        $progress([
                            'type' => 'duration_failed',
                            'step' => $step,
                            'total_steps' => $totalSteps,
                            'instagram_account_id' => $account->id,
                            'duration' => $duration,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $delayMs = (int) env('INSTAGRAM_MEDIA_SYNC_STEP_DELAY_MS', 500);
                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }

            foreach ($this->cacheDurations as $cacheDuration) {
                [$since, $until] = $this->resolveDateRange($cacheDuration);
                $this->storeDurationPostsCache((int) $account->id, $cacheDuration, $since, $until, $posts);
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * @param  array<int, array<string, mixed>>  $posts
     */
    protected function storeDurationPostsCache(int $instagramAccountId, string $duration, string $since, string $until, array $posts): void
    {
        $filtered = $this->filterPostsForRange($posts, $since, $until);
        $cacheKey = $this->durationPostsCacheKey($instagramAccountId, $duration, $since, $until);
        Cache::put($cacheKey, $filtered, now()->addHours(24));
    }

    /**
     * @param  array<int, array<string, mixed>>  $posts
     * @return array<int, array<string, mixed>>
     */
    protected function filterPostsForRange(array $posts, string $since, string $until): array
    {
        $fromTs = strtotime($since.' 00:00:00');
        $toTs = strtotime($until.' 23:59:59');

        return array_values(array_filter($posts, function ($post) use ($fromTs, $toTs) {
            if (! is_array($post)) {
                return false;
            }
            $created = $post['created_time'] ?? null;
            $ts = is_string($created) ? strtotime($created) : false;

            return $ts !== false && $ts >= $fromTs && $ts <= $toTs;
        }));
    }

    public function durationPostsCacheKey(int $instagramAccountId, string $duration, string $since, string $until): string
    {
        return implode(':', [
            'instagram_posts_by_duration',
            'v1',
            'account',
            $instagramAccountId,
            'duration',
            $duration,
            'since',
            $since,
            'until',
            $until,
        ]);
    }
}
