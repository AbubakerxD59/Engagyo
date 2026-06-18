<?php

namespace App\Services;

use App\Models\Youtube;
use App\Models\YoutubeInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class YouTubeInsightsSyncService
{
    protected array $durations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year', 'full_year'];

    protected int $maxRecordsPerDuration = 7;

    public function __construct(protected YouTubeAnalyticsService $youTubeAnalyticsService) {}

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

    public function syncChannelInsights(Youtube $account, string $duration): bool
    {
        if (empty($account->access_token) || empty($account->channel_id)) {
            return false;
        }

        $tokenCheck = YouTubeService::validateToken($account);
        if (! ($tokenCheck['success'] ?? false)) {
            return false;
        }

        [$since, $until] = $this->resolveDateRange($duration);
        $insights = $this->youTubeAnalyticsService->getAccountInsightsWithComparison($account, $since, $until);

        YoutubeInsight::updateOrCreate(
            [
                'youtube_id' => $account->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'insights' => $insights,
                'synced_at' => now(),
            ]
        );

        $this->pruneInsights((int) $account->id, $duration);

        return true;
    }

    protected function pruneInsights(int $youtubeId, string $duration): void
    {
        $idsToKeep = YoutubeInsight::where('youtube_id', $youtubeId)
            ->where('duration', $duration)
            ->orderByDesc('synced_at')
            ->limit($this->maxRecordsPerDuration)
            ->pluck('id');

        YoutubeInsight::where('youtube_id', $youtubeId)
            ->where('duration', $duration)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
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
            foreach ($this->durations as $duration) {
                try {
                    if ($this->syncChannelInsights($account, $duration)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('YouTube insights sync failed', [
                        'youtube_id' => $account->id,
                        'duration' => $duration,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
