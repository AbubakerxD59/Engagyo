<?php

namespace App\Services;

use App\Models\InstagramAccount;
use App\Models\InstagramInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InstagramInsightsSyncService
{
    protected array $durations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year', 'full_year'];

    protected int $maxRecordsPerDuration = 7;

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

    public function syncAccountInsights(InstagramAccount $account, string $duration): bool
    {
        if (empty($account->ig_user_id) || empty($account->access_token)) {
            return false;
        }

        $tokenCheck = FacebookService::validateToken($account);
        if (! ($tokenCheck['success'] ?? false)) {
            return false;
        }

        [$since, $until] = $this->resolveDateRange($duration);
        $insights = $this->instagramAnalyticsService->getAccountInsightsWithComparison($account, $since, $until);

        InstagramInsight::updateOrCreate(
            [
                'instagram_account_id' => $account->id,
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

    protected function pruneInsights(int $instagramAccountId, string $duration): void
    {
        $idsToKeep = InstagramInsight::where('instagram_account_id', $instagramAccountId)
            ->where('duration', $duration)
            ->orderByDesc('synced_at')
            ->limit($this->maxRecordsPerDuration)
            ->pluck('id');

        InstagramInsight::where('instagram_account_id', $instagramAccountId)
            ->where('duration', $duration)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }

    /**
     * @return array{synced: int, failed: int}
     */
    public function syncAll(): array
    {
        $accounts = InstagramAccount::query()
            ->whereNotNull('ig_user_id')
            ->where('ig_user_id', '!=', '')
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            foreach ($this->durations as $duration) {
                try {
                    if ($this->syncAccountInsights($account, $duration)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Instagram insights sync failed', [
                        'instagram_account_id' => $account->id,
                        'duration' => $duration,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
