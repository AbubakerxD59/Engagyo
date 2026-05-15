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
        $this->clearPostsCaches((int) $account->id, (int) ($account->user_id ?? 0));

        return true;
    }

    protected function clearPostsCaches(int $tiktokId, int $userId): void
    {
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
            Cache::forget($this->analyticsPostsCacheKey($userId, $tiktokId, $duration, $since, $until));
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
