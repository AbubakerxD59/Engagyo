<?php

namespace App\Services;

use App\Models\Tiktok;
use App\Models\TiktokPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TiktokPostsSyncService
{
    private const POSTS_CACHE_TTL_HOURS = 3;

    public function __construct(protected TikTokAnalyticsService $tikTokAnalyticsService) {}

    /**
     * Sync all public videos for the account (up to API pagination cap).
     */
    public function syncTiktokPosts(Tiktok $account): bool
    {
        if (empty($account->access_token)) {
            return false;
        }

        $tokenCheck = TikTokService::validateToken($account);
        if (! ($tokenCheck['success'] ?? false)) {
            return false;
        }

        $rawVideos = $this->tikTokAnalyticsService->listAllVideosForSync($account);
        $normalized = [];
        foreach ($rawVideos as $video) {
            if (! is_array($video)) {
                continue;
            }
            $normalized[] = $this->tikTokAnalyticsService->normalizeVideoToPost($video);
        }

        TiktokPost::persistFromAnalyticsPosts((int) $account->id, $normalized);
        $this->refreshPostsCaches($account, $normalized);

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $allPosts
     */
    protected function refreshPostsCaches(Tiktok $account, array $allPosts): void
    {
        $userId = (int) ($account->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $today = Carbon::today();
        $ranges = [
            'last_7' => [$today->copy()->subDays(7)->format('Y-m-d'), $today->format('Y-m-d')],
            'last_28' => [$today->copy()->subDays(28)->format('Y-m-d'), $today->format('Y-m-d')],
            'last_90' => [$today->copy()->subDays(90)->format('Y-m-d'), $today->format('Y-m-d')],
            'this_month' => [$today->copy()->startOfMonth()->format('Y-m-d'), $today->format('Y-m-d')],
            'this_year' => [$today->copy()->startOfYear()->format('Y-m-d'), $today->format('Y-m-d')],
            'full_year' => [$today->copy()->subYear()->format('Y-m-d'), $today->format('Y-m-d')],
        ];

        foreach ($ranges as $duration => [$since, $until]) {
            $filtered = array_values(array_filter($allPosts, function ($post) use ($since, $until) {
                if (empty($post['created_time'])) {
                    return false;
                }
                try {
                    $created = Carbon::parse((string) $post['created_time']);

                    return $created->between(
                        Carbon::parse($since)->startOfDay(),
                        Carbon::parse($until)->endOfDay()
                    );
                } catch (\Throwable) {
                    return false;
                }
            }));

            $key = $this->analyticsPostsCacheKey($userId, (int) $account->id, $duration, $since, $until);
            Cache::forget($key);
            Cache::put($key, $filtered, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
        }
    }

    private function analyticsPostsCacheKey(int $userId, int $tiktokId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'analytics_posts',
            'v2',
            'user',
            $userId,
            'platform',
            'tiktok',
            'account',
            $tiktokId,
            'duration',
            $duration,
            'since',
            (string) ($since ?? ''),
            'until',
            (string) ($until ?? ''),
        ]);
    }

    public function syncAll(): array
    {
        $accounts = Tiktok::withoutGlobalScopes()
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                if ($this->syncTiktokPosts($account)) {
                    $synced++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('TikTok posts sync failed', [
                    'tiktok_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
