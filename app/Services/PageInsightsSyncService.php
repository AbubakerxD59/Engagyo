<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PageInsightsSyncService
{
    protected FacebookService $facebookService;

    protected array $durations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year'];

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
            default => [$today->copy()->subDays(28)->format('Y-m-d'), $today->format('Y-m-d')],
        };
    }

    /**
     * Sync page insights for a single page and duration.
     */
    public function syncPageInsights(Page $page, string $duration): bool
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
        $insights = $this->facebookService->getPageInsightsWithComparison(
            $page->page_id,
            $accessToken,
            $since,
            $until
        );

        PageInsight::updateOrCreate(
            [
                'page_id' => $page->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'insights' => $insights,
                'synced_at' => now(),
            ]
        );

        return true;
    }

    /**
     * Sync page insights for all Facebook pages and all durations.
     */
    public function syncAll(): array
    {
        $pages = Page::withoutGlobalScopes()
            ->whereNotNull('page_id')
            ->whereNotNull('access_token')
            ->where('page_id', '!=', '')
            ->where('access_token', '!=', '')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($pages as $page) {
            foreach ($this->durations as $duration) {
                try {
                    if ($this->syncPageInsights($page, $duration)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Page insights sync failed', [
                        'page_id' => $page->id,
                        'duration' => $duration,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
