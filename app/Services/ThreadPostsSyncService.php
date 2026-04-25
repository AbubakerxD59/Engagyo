<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\ThreadInsight;
use App\Models\ThreadPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThreadPostsSyncService
{
    private const POSTS_CACHE_TTL_HOURS = 3;

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

    public function syncThreadPosts(Thread $thread, string $duration): bool
    {
        if (empty($thread->threads_id) || empty($thread->access_token) || ! $thread->validToken()) {
            return false;
        }

        [$since, $until] = $this->resolveDateRange($duration);
        $posts = $this->threadsAnalyticsService->getPostsWithInsights($thread, $since, $until);

        ThreadPost::updateOrCreate(
            [
                'thread_id' => $thread->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'posts' => $posts,
                'synced_at' => now(),
            ]
        );

        $this->refreshPostsCache($thread, $duration, $since, $until, $posts);
        $this->pruneThreadPosts($thread->id, $duration);

        return true;
    }

    protected function refreshPostsCache(Thread $thread, string $duration, string $since, string $until, array $posts): void
    {
        $userId = (int) ($thread->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $key = $this->analyticsPostsCacheKey($userId, (int) $thread->id, $duration, $since, $until);
        Cache::forget($key);
        Cache::put($key, $posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
    }

    protected function pruneThreadPosts(int $threadId, string $duration): void
    {
        $idsToKeep = ThreadPost::where('thread_id', $threadId)
            ->where('duration', $duration)
            ->orderByDesc('synced_at')
            ->limit($this->maxRecordsPerDuration)
            ->pluck('id');

        ThreadPost::where('thread_id', $threadId)
            ->where('duration', $duration)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }

    private function analyticsPostsCacheKey(int $userId, int $threadId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'analytics_posts',
            'v2',
            'user',
            $userId,
            'platform',
            'threads',
            'account',
            $threadId,
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
        $threadIds = ThreadInsight::distinct()->pluck('thread_id');
        $threads = Thread::withoutGlobalScopes()
            ->whereIn('id', $threadIds)
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
                    if ($this->syncThreadPosts($thread, $duration)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Thread posts sync failed', [
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
