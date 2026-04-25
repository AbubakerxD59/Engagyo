<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\ThreadInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ThreadInsightsSyncService
{
    protected array $durations = ['last_7', 'last_28', 'last_90', 'this_month', 'this_year'];

    protected int $maxRecordsPerDuration = 7;

    public function __construct(protected ThreadsAnalyticsService $threadsAnalyticsService) {}

    /**
     * @return array{0:string,1:string}
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

    public function syncThreadInsights(Thread $thread, string $duration): bool
    {
        if (empty($thread->threads_id) || empty($thread->access_token) || ! $thread->validToken()) {
            return false;
        }

        [$since, $until] = $this->resolveDateRange($duration);
        $insights = $this->threadsAnalyticsService->getAccountInsightsWithComparison($thread, $since, $until);

        ThreadInsight::updateOrCreate(
            [
                'thread_id' => $thread->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'insights' => $insights,
                'synced_at' => now(),
            ]
        );

        $this->pruneThreadInsights($thread->id, $duration);

        return true;
    }

    protected function pruneThreadInsights(int $threadId, string $duration): void
    {
        $idsToKeep = ThreadInsight::where('thread_id', $threadId)
            ->where('duration', $duration)
            ->orderByDesc('synced_at')
            ->limit($this->maxRecordsPerDuration)
            ->pluck('id');

        ThreadInsight::where('thread_id', $threadId)
            ->where('duration', $duration)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }

    public function syncAll(): array
    {
        $threads = Thread::withoutGlobalScopes()
            ->whereNotNull('threads_id')
            ->whereNotNull('access_token')
            ->where('threads_id', '!=', '')
            ->where('access_token', '!=', '')
            ->get();

        $synced = 0;
        $failed = 0;
        foreach ($threads as $thread) {
            foreach ($this->durations as $duration) {
                try {
                    if ($this->syncThreadInsights($thread, $duration)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Thread insights sync failed', [
                        'thread_id' => $thread->id,
                        'duration' => $duration,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
