<?php

namespace App\Services;

use App\Models\Board;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PinterestBoardInsightsSyncService
{
    protected array $durations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year'];

    public function __construct(protected PinterestBoardAnalyticsService $pinterestBoardAnalyticsService) {}

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
            'this_month' => [$today->copy()->startOfMonth()->format('Y-m-d'), $today->format('Y-m-d')],
            'this_year' => [$today->copy()->startOfYear()->format('Y-m-d'), $today->format('Y-m-d')],
            default => [$today->copy()->subDays(28)->format('Y-m-d'), $today->format('Y-m-d')],
        };
    }

    public function syncBoard(Board $board, string $duration): bool
    {
        if (empty($board->board_id)) {
            return false;
        }

        $token = $this->pinterestBoardAnalyticsService->resolveAccessToken($board);
        if ($token === null) {
            return false;
        }

        [$since, $until] = $this->resolveDateRange($duration);

        try {
            $this->pinterestBoardAnalyticsService->syncBoard($board, $since, $until, $duration);
        } catch (\Throwable $e) {
            Log::warning('Pinterest board analytics sync failed', [
                'board_id' => $board->id,
                'duration' => $duration,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * @return array{synced: int, failed: int}
     */
    public function syncAll(): array
    {
        $boards = Board::withoutGlobalScopes()
            ->with('pinterest')
            ->whereNotNull('board_id')
            ->where('board_id', '!=', '')
            ->whereHas('pinterest', function ($q) {
                $q->whereNotNull('access_token')
                    ->where('access_token', '!=', '');
            })
            ->get();

        $synced = 0;
        $failed = 0;
        foreach ($boards as $board) {
            foreach ($this->durations as $duration) {
                try {
                    if ($this->syncBoard($board, $duration)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Pinterest board insights sync failed', [
                        'board_id' => $board->id,
                        'duration' => $duration,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
