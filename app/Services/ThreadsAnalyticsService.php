<?php

namespace App\Services;

use App\Models\Thread;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThreadsAnalyticsService
{
    /**
     * Fetch Threads account insights with previous-period comparison.
     */
    public function getAccountInsightsWithComparison(Thread $thread, ?string $since, ?string $until): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);

        $currentPosts = $this->getPostsWithInsights($thread, $since, $until);
        $current = $this->aggregateInsights($thread, $currentPosts, $since, $until);

        $sinceDt = Carbon::parse($since);
        $untilDt = Carbon::parse($until);
        $periodDays = $sinceDt->diffInDays($untilDt) + 1;
        $prevUntilDt = $sinceDt->copy()->subDay();
        $prevSinceDt = $prevUntilDt->copy()->subDays($periodDays - 1);

        $prevPosts = $this->getPostsWithInsights(
            $thread,
            $prevSinceDt->format('Y-m-d'),
            $prevUntilDt->format('Y-m-d')
        );
        $previous = $this->aggregateInsights(
            $thread,
            $prevPosts,
            $prevSinceDt->format('Y-m-d'),
            $prevUntilDt->format('Y-m-d')
        );

        $metrics = ['followers', 'reach', 'video_views', 'engagements'];
        $current['comparison'] = [];
        foreach ($metrics as $metric) {
            $curr = (float) ($current[$metric] ?? 0);
            $prev = (float) ($previous[$metric] ?? 0);
            $diff = $curr - $prev;
            if ($prev == 0.0) {
                $current['comparison'][$metric] = [
                    'change' => $curr > 0 ? 100.0 : 0.0,
                    'direction' => $curr > 0 ? 'up' : null,
                    'diff' => $curr > 0 ? $diff : 0,
                ];
                continue;
            }

            $change = round((($curr - $prev) / $prev) * 100, 1);
            $current['comparison'][$metric] = [
                'change' => $change,
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : null),
                'diff' => $diff,
            ];
        }

        return $current;
    }

    /**
     * Fetch Threads posts with normalized post-level insights.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPostsWithInsights(Thread $thread, ?string $since, ?string $until): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);
        $accessToken = (string) ($thread->access_token ?? '');
        $threadsUserId = (string) ($thread->threads_id ?? '');
        if ($accessToken === '' || $threadsUserId === '') {
            return [];
        }

        $allPosts = [];
        $after = null;
        do {
            $params = [
                'fields' => 'id,text,media_type,timestamp,permalink,media_url,thumbnail_url',
                'limit' => 100,
                'access_token' => $accessToken,
            ];
            if ($after) {
                $params['after'] = $after;
            }

            $response = $this->graphGet("/{$threadsUserId}/threads", $params);
            $rows = $response['data'] ?? [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $created = isset($row['timestamp']) ? Carbon::parse((string) $row['timestamp']) : null;
                if (! $created) {
                    continue;
                }
                $createdDate = $created->format('Y-m-d');
                if ($createdDate < $since || $createdDate > $until) {
                    continue;
                }

                $postInsights = $this->fetchPostInsights((string) ($row['id'] ?? ''), $accessToken);
                $likes = (int) ($postInsights['likes'] ?? 0);
                $replies = (int) ($postInsights['replies'] ?? 0);
                $reposts = (int) ($postInsights['reposts'] ?? 0);
                $quotes = (int) ($postInsights['quotes'] ?? 0);
                $shares = (int) ($postInsights['shares'] ?? 0);
                $views = (int) ($postInsights['views'] ?? 0);
                $engagements = $likes + $replies + $reposts + $quotes + $shares;
                $rate = $views > 0 ? round(($engagements / $views) * 100, 1) : 0.0;

                $allPosts[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'message' => (string) ($row['text'] ?? ''),
                    'created_time' => $created->toIso8601String(),
                    'full_picture' => (string) ($row['media_url'] ?? $row['thumbnail_url'] ?? ''),
                    'permalink_url' => (string) ($row['permalink'] ?? ''),
                    'media_type' => (string) ($row['media_type'] ?? ''),
                    'insights' => [
                        'post_impressions' => $views,
                        'post_reach' => $views,
                        'post_reactions' => $likes,
                        'post_clicks' => $quotes,
                        'post_engagement_rate' => $rate,
                        'likes' => $likes,
                        'replies' => $replies,
                        'reposts' => $reposts,
                        'quotes' => $quotes,
                        'shares' => $shares,
                        'views' => $views,
                    ],
                ];
            }

            $after = $response['paging']['cursors']['after'] ?? null;
        } while (! empty($after));

        usort($allPosts, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_time'] ?? ''), (string) ($a['created_time'] ?? ''));
        });

        return $allPosts;
    }

    /**
     * Aggregate normalized account metrics from post insights.
     *
     * @param  array<int, array<string, mixed>>  $posts
     * @return array<string, mixed>
     */
    private function aggregateInsights(Thread $thread, array $posts, string $since, string $until): array
    {
        $followers = $this->fetchFollowerCount((string) $thread->access_token);
        $reach = 0;
        $videoViews = 0;
        $engagements = 0;
        $reachByDay = [];
        $videoViewsByDay = [];
        $engagementsByDay = [];

        foreach ($posts as $post) {
            $created = Carbon::parse((string) ($post['created_time'] ?? now()->toIso8601String()));
            $day = $created->format('Y-m-d');
            $ins = is_array($post['insights'] ?? null) ? $post['insights'] : [];
            $views = (int) ($ins['views'] ?? 0);
            $likes = (int) ($ins['likes'] ?? 0);
            $replies = (int) ($ins['replies'] ?? 0);
            $reposts = (int) ($ins['reposts'] ?? 0);
            $quotes = (int) ($ins['quotes'] ?? 0);
            $shares = (int) ($ins['shares'] ?? 0);
            $postEngagements = $likes + $replies + $reposts + $quotes + $shares;

            $reach += $views;
            $engagements += $postEngagements;
            $reachByDay[$day] = (int) (($reachByDay[$day] ?? 0) + $views);
            $engagementsByDay[$day] = (int) (($engagementsByDay[$day] ?? 0) + $postEngagements);

            if (strtoupper((string) ($post['media_type'] ?? '')) === 'VIDEO') {
                $videoViews += $views;
                $videoViewsByDay[$day] = (int) (($videoViewsByDay[$day] ?? 0) + $views);
            }
        }

        ksort($reachByDay);
        ksort($videoViewsByDay);
        ksort($engagementsByDay);

        return [
            'followers' => $followers,
            'reach' => $reach,
            'video_views' => $videoViews,
            'engagements' => $engagements,
            'followers_by_day' => [], // Threads API does not provide historical follower curve in this flow.
            'reach_by_day' => $reachByDay,
            'video_views_by_day' => $videoViewsByDay,
            'engagements_by_day' => $engagementsByDay,
            'range' => ['since' => $since, 'until' => $until],
        ];
    }

    private function fetchFollowerCount(string $accessToken): int
    {
        if ($accessToken === '') {
            return 0;
        }

        $response = $this->graphGet('/me', [
            'fields' => 'followers_count',
            'access_token' => $accessToken,
        ]);

        return (int) ($response['followers_count'] ?? 0);
    }

    /**
     * @return array{views:int,likes:int,replies:int,reposts:int,quotes:int,shares:int}
     */
    private function fetchPostInsights(string $postId, string $accessToken): array
    {
        if ($postId === '' || $accessToken === '') {
            return ['views' => 0, 'likes' => 0, 'replies' => 0, 'reposts' => 0, 'quotes' => 0, 'shares' => 0];
        }

        $response = $this->graphGet('/'.$postId.'/insights', [
            'metric' => 'views,likes,replies,reposts,quotes,shares',
            'access_token' => $accessToken,
        ]);
        $data = $response['data'] ?? [];
        $metrics = ['views' => 0, 'likes' => 0, 'replies' => 0, 'reposts' => 0, 'quotes' => 0, 'shares' => 0];

        if (! is_array($data)) {
            return $metrics;
        }

        foreach ($data as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $name = (string) ($entry['name'] ?? '');
            if (! array_key_exists($name, $metrics)) {
                continue;
            }
            $value = $entry['values'][0]['value'] ?? $entry['value'] ?? 0;
            $metrics[$name] = (int) $value;
        }

        return $metrics;
    }

    private function graphGet(string $path, array $query): array
    {
        $url = 'https://graph.threads.net/v1.0'.$path;
        $response = Http::acceptJson()->timeout(60)->get($url, $query);
        if (! $response->successful()) {
            Log::warning('Threads analytics request failed', [
                'path' => $path,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        }

        return is_array($response->json()) ? $response->json() : [];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function normalizeDateRange(?string $since, ?string $until): array
    {
        $untilDate = $until ? Carbon::parse($until) : Carbon::today();
        $sinceDate = $since ? Carbon::parse($since) : $untilDate->copy()->subDays(28);
        if ($sinceDate->gt($untilDate)) {
            $sinceDate = $untilDate->copy();
        }

        return [$sinceDate->format('Y-m-d'), $untilDate->format('Y-m-d')];
    }
}
