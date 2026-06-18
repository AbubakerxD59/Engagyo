<?php

namespace App\Services;

use App\Models\Youtube;
use App\Models\YoutubePost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YouTubePostsSyncService
{
    private const POSTS_CACHE_TTL_HOURS = 3;

    public function __construct(protected YouTubeAnalyticsService $youTubeAnalyticsService) {}

    public function syncChannelVideos(Youtube $account): bool
    {
        if (empty($account->access_token) || empty($account->channel_id)) {
            return false;
        }

        $tokenCheck = YouTubeService::validateToken($account);
        if (! ($tokenCheck['success'] ?? false)) {
            return false;
        }

        $normalized = $this->youTubeAnalyticsService->listAllVideosForSync($account);
        YoutubePost::persistFromAnalyticsPosts((int) $account->id, $normalized);

        $account->update(['last_fetch' => now()]);
        $this->clearPostsCaches((int) $account->id, (int) ($account->user_id ?? 0));

        return true;
    }

    public function syncLatestVideosForChannel(Youtube $account): bool
    {
        return $this->syncChannelVideos($account);
    }

    protected function clearPostsCaches(int $youtubeId, int $userId): void
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
            Cache::forget($this->analyticsPostsCacheKey($userId, $youtubeId, $duration, $since, $until));
        }
    }

    private function analyticsPostsCacheKey(int $userId, int $youtubeId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'analytics_posts',
            'v2',
            'user',
            $userId,
            'platform',
            'youtube',
            'account',
            $youtubeId,
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
        $accounts = Youtube::withoutGlobalScopes()
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '')
            ->whereNotNull('channel_id')
            ->where('channel_id', '!=', '')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                if ($this->syncChannelVideos($account)) {
                    $synced++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('YouTube posts sync failed', [
                    'youtube_id' => $account->id,
                    'channel_id' => $account->channel_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
