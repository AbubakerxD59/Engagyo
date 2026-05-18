<?php

namespace App\Services;

use App\Models\InstagramAccount;
use App\Models\InstagramPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramAnalyticsService
{
    /** @var list<string> */
    private const USER_INSIGHT_METRICS = [
        'reach',
        'follower_count',
        'profile_views',
        'accounts_engaged',
        'total_interactions',
    ];

    /** @var list<string> */
    private const MEDIA_INSIGHT_METRICS = [
        'impressions',
        'reach',
        'saved',
        'likes',
        'comments',
        'shares',
        'total_interactions',
    ];

    /**
     * @return array{0: string, 1: string}
     */
    public function normalizeDateRange(?string $since, ?string $until): array
    {
        $until = $until ?: date('Y-m-d');
        $since = $since ?: date('Y-m-d', strtotime('-28 days', strtotime($until)));

        if ($since > $until) {
            $until = $since;
        }

        return [$since, $until];
    }

    /**
     * @return array followers, reach, video_views, engagements, *_by_day, comparison
     */
    public function getAccountInsightsWithComparison(InstagramAccount $account, ?string $since, ?string $until): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);

        $current = $this->getAccountInsights($account, $since, $until);

        $sinceDt = Carbon::parse($since);
        $untilDt = Carbon::parse($until);
        $periodDays = $sinceDt->diffInDays($untilDt) + 1;
        $prevUntilDt = $sinceDt->copy()->subDay();
        $prevSinceDt = $prevUntilDt->copy()->subDays($periodDays - 1);

        $previous = $this->getAccountInsights(
            $account,
            $prevSinceDt->format('Y-m-d'),
            $prevUntilDt->format('Y-m-d')
        );

        $metrics = ['followers', 'reach', 'video_views', 'engagements'];
        $current['comparison'] = [];

        foreach ($metrics as $metric) {
            $curr = $current[$metric] ?? null;
            $prev = $previous[$metric] ?? null;
            $current['comparison'][$metric] = ['change' => null, 'direction' => null];

            if ($curr === null || $prev === null || ! is_numeric($curr) || ! is_numeric($prev)) {
                continue;
            }

            $curr = (float) $curr;
            $prev = (float) $prev;
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
     * @return array<string, mixed>
     */
    public function getAccountInsights(InstagramAccount $account, ?string $since, ?string $until): array
    {
        $result = [
            'followers' => null,
            'reach' => null,
            'video_views' => null,
            'engagements' => null,
            'followers_by_day' => [],
            'reach_by_day' => [],
            'video_views_by_day' => [],
            'engagements_by_day' => [],
        ];

        [$since, $until] = $this->normalizeDateRange($since, $until);
        $token = $this->resolveAccessToken($account);
        $igUserId = (string) ($account->ig_user_id ?? '');

        if ($token === '' || $igUserId === '') {
            return $result;
        }

        $sinceUnix = Carbon::parse($since)->startOfDay()->timestamp;
        $untilUnix = Carbon::parse($until)->endOfDay()->timestamp;

        $response = $this->graphGet($account, '/'.$igUserId.'/insights', [
            'metric' => implode(',', self::USER_INSIGHT_METRICS),
            'period' => 'day',
            'since' => $sinceUnix,
            'until' => $untilUnix,
            'access_token' => $token,
        ]);

        $data = $response['data'] ?? [];
        if (! is_array($data)) {
            return $result;
        }

        $totals = [
            'reach' => 0,
            'profile_views' => 0,
            'accounts_engaged' => 0,
            'total_interactions' => 0,
        ];

        foreach ($data as $metricNode) {
            if (! is_array($metricNode)) {
                continue;
            }

            $name = (string) ($metricNode['name'] ?? '');
            $values = $metricNode['values'] ?? [];
            if (! is_array($values) || $values === []) {
                continue;
            }

            $byDayKey = match ($name) {
                'follower_count' => 'followers_by_day',
                'reach' => 'reach_by_day',
                'profile_views' => 'video_views_by_day',
                'total_interactions', 'accounts_engaged' => 'engagements_by_day',
                default => null,
            };

            if ($name === 'follower_count') {
                $last = end($values);
                $val = is_array($last) ? ($last['value'] ?? null) : null;
                $result['followers'] = is_numeric($val) ? (int) $val : null;

                foreach ($values as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $v = $item['value'] ?? null;
                    $endTime = $item['end_time'] ?? null;
                    if ($byDayKey && is_numeric($v) && $endTime) {
                        $dateStr = Carbon::parse($endTime)->format('Y-m-d');
                        $result[$byDayKey][$dateStr] = (int) (($result[$byDayKey][$dateStr] ?? 0) + (int) $v);
                    }
                }
            } elseif (array_key_exists($name, $totals)) {
                foreach ($values as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $v = $item['value'] ?? 0;
                    $val = is_numeric($v) ? (int) $v : 0;
                    $totals[$name] += $val;
                    $endTime = $item['end_time'] ?? null;
                    if ($byDayKey && $endTime) {
                        $dateStr = Carbon::parse($endTime)->format('Y-m-d');
                        $result[$byDayKey][$dateStr] = (int) (($result[$byDayKey][$dateStr] ?? 0) + $val);
                    }
                }
            }
        }

        $result['reach'] = $totals['reach'] ?: null;
        $result['video_views'] = $totals['profile_views'] ?: null;
        $result['engagements'] = ($totals['total_interactions'] ?: $totals['accounts_engaged']) ?: null;

        foreach (['followers_by_day', 'reach_by_day', 'video_views_by_day', 'engagements_by_day'] as $key) {
            ksort($result[$key]);
        }

        return $result;
    }

    /**
     * Fetch Instagram media with insights; persist rows when fetched live.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMediaWithInsights(InstagramAccount $account, ?string $since, ?string $until, int $limit = 100): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);
        $igUserId = (string) ($account->ig_user_id ?? '');

        if ($igUserId === '') {
            return [];
        }

        $stored = $this->getStoredMediaWithInsights((int) $account->id, $since, $until);
        if ($stored !== []) {
            return $stored;
        }

        $token = $this->resolveAccessToken($account);
        if ($token === '') {
            return [];
        }

        $media = $this->fetchMediaList($account, $token, $since, $until, $limit);
        if ($media === []) {
            return [];
        }

        $this->attachMediaInsights($account, $token, $media);
        $media = $this->normalizeMediaPosts($media);

        InstagramPost::persistFromAnalyticsPosts((int) $account->id, $igUserId, $media);

        return $media;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getStoredMediaWithInsights(int $instagramAccountId, string $since, string $until): array
    {
        $rows = InstagramPost::forCreatedDateRange($instagramAccountId, $since, $until);
        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->map(fn (InstagramPost $row) => $row->toAnalyticsPostArray())->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchMediaList(InstagramAccount $account, string $token, string $since, string $until, int $limit): array
    {
        $igUserId = (string) $account->ig_user_id;
        $posts = [];
        $after = null;
        $fetched = 0;

        do {
            $params = [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
                'limit' => min(50, $limit),
                'access_token' => $token,
            ];
            if ($after) {
                $params['after'] = $after;
            }

            $response = $this->graphGet($account, '/'.$igUserId.'/media', $params);
            $rows = $response['data'] ?? [];
            if (! is_array($rows)) {
                break;
            }

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

                $posts[] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'caption' => (string) ($row['caption'] ?? ''),
                    'message' => (string) ($row['caption'] ?? ''),
                    'media_type' => (string) ($row['media_type'] ?? ''),
                    'type' => strtolower((string) ($row['media_type'] ?? '')),
                    'media_url' => (string) ($row['media_url'] ?? ''),
                    'thumbnail_url' => (string) ($row['thumbnail_url'] ?? ''),
                    'full_picture' => (string) ($row['media_url'] ?? $row['thumbnail_url'] ?? ''),
                    'permalink_url' => (string) ($row['permalink'] ?? ''),
                    'created_time' => $created->toIso8601String(),
                    'like_count' => (int) ($row['like_count'] ?? 0),
                    'comments_count' => (int) ($row['comments_count'] ?? 0),
                    'insights' => [],
                ];

                $fetched++;
                if ($fetched >= $limit) {
                    break 2;
                }
            }

            $after = $response['paging']['cursors']['after'] ?? null;
        } while (! empty($after) && $fetched < $limit);

        usort($posts, static fn (array $a, array $b): int => strcmp((string) ($b['created_time'] ?? ''), (string) ($a['created_time'] ?? '')));

        return $posts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $media
     */
    private function attachMediaInsights(InstagramAccount $account, string $token, array &$media): void
    {
        $metrics = implode(',', self::MEDIA_INSIGHT_METRICS);
        $batchSize = 25;
        $base = $this->graphBaseUrl($account);

        foreach (array_chunk($media, $batchSize, true) as $chunk) {
            $batch = [];
            $indexMap = [];

            foreach ($chunk as $offset => $item) {
                $mediaId = (string) ($item['id'] ?? '');
                if ($mediaId === '') {
                    continue;
                }
                $indexMap[] = $offset;
                $batch[] = [
                    'method' => 'GET',
                    'relative_url' => $mediaId.'/insights?metric='.$metrics,
                ];
            }

            if ($batch === []) {
                continue;
            }

            try {
                $response = Http::asForm()
                    ->acceptJson()
                    ->timeout(120)
                    ->post($base.'/', [
                        'batch' => json_encode($batch),
                        'access_token' => $token,
                    ]);

                if (! $response->successful()) {
                    continue;
                }

                $batchResponses = $response->json();
                if (! is_array($batchResponses)) {
                    continue;
                }

                foreach ($indexMap as $i => $offset) {
                    $insightsResp = $batchResponses[$i] ?? null;
                    if (! is_array($insightsResp)) {
                        continue;
                    }

                    $code = (int) ($insightsResp['code'] ?? 0);
                    $body = $insightsResp['body'] ?? '{}';
                    $data = is_string($body) ? json_decode($body, true) : $body;
                    if ($code !== 200 || ! is_array($data) || ! isset($data['data'])) {
                        continue;
                    }

                    $parsed = [];
                    foreach ($data['data'] as $metricNode) {
                        if (! is_array($metricNode)) {
                            continue;
                        }
                        $name = (string) ($metricNode['name'] ?? '');
                        $values = $metricNode['values'] ?? [];
                        $first = is_array($values) && $values !== [] ? reset($values) : null;
                        $val = is_array($first) ? ($first['value'] ?? 0) : 0;
                        if (is_array($val)) {
                            $val = array_sum($val);
                        }
                        $parsed[$name] = is_numeric($val) ? (int) $val : 0;
                    }

                    $media[$offset]['insights'] = $parsed;
                }
            } catch (\Throwable $e) {
                Log::warning('Instagram media insights batch failed', [
                    'instagram_account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            usleep(150000);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $media
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMediaPosts(array $media): array
    {
        foreach ($media as &$post) {
            $insights = is_array($post['insights'] ?? null) ? $post['insights'] : [];
            $likes = (int) ($insights['likes'] ?? $post['like_count'] ?? 0);
            $comments = (int) ($insights['comments'] ?? $post['comments_count'] ?? 0);
            $impressions = (int) ($insights['impressions'] ?? 0);
            $reach = (int) ($insights['reach'] ?? 0);
            $saved = (int) ($insights['saved'] ?? 0);
            $shares = (int) ($insights['shares'] ?? 0);
            $engagements = (int) ($insights['total_interactions'] ?? ($likes + $comments + $saved + $shares));

            $post['insights'] = [
                'post_impressions' => $impressions,
                'post_reach' => $reach,
                'post_reactions' => $likes,
                'post_comments' => $comments,
                'post_saves' => $saved,
                'post_shares' => $shares,
                'post_engagement_rate' => $reach > 0
                    ? round(($engagements / $reach) * 100, 2)
                    : ($impressions > 0 ? round(($engagements / $impressions) * 100, 2) : 0),
            ];
        }
        unset($post);

        return $media;
    }

    private function resolveAccessToken(InstagramAccount $account): string
    {
        $check = FacebookService::validateToken($account);
        if (! ($check['success'] ?? false)) {
            return '';
        }

        return (string) ($check['access_token'] ?? $account->access_token ?? '');
    }

    private function graphBaseUrl(InstagramAccount $account): string
    {
        $v = ltrim((string) config('services.instagram.graph_version', 'v21.0'), '/');

        if ($account->usesInstagramLogin()) {
            return 'https://graph.instagram.com/'.$v;
        }

        return 'https://graph.facebook.com/'.$v;
    }

    /**
     * @return array<string, mixed>
     */
    private function graphGet(InstagramAccount $account, string $path, array $query): array
    {
        $url = $this->graphBaseUrl($account).$path;

        try {
            $response = Http::acceptJson()->timeout(90)->get($url, $query);
            if (! $response->successful()) {
                Log::warning('Instagram analytics request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return is_array($response->json()) ? $response->json() : [];
        } catch (\Throwable $e) {
            Log::warning('Instagram analytics request exception', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
