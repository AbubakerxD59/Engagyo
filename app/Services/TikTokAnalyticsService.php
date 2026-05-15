<?php

namespace App\Services;

use App\Models\Tiktok;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TikTokAnalyticsService
{
    private const BASE_URL = 'https://open.tiktokapis.com/v2/';

    private const VIDEO_LIST_FIELDS = 'id,create_time,cover_image_url,share_url,video_description,title,duration,like_count,comment_count,share_count,view_count';

    /** Safety cap when paginating public videos. */
    private const MAX_VIDEOS_PER_SYNC = 100;

    public function __construct(protected HttpService $http) {}

    /**
     * Account insights for a date range with previous-period comparison.
     * Uses TikTok user.info.stats (snapshot) plus video.list aggregates for the range.
     *
     * @return array<string, mixed>
     */
    public function getAccountInsightsWithComparison(Tiktok $account, ?string $since, ?string $until): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);

        $accessToken = $this->resolveAccessToken($account);
        if ($accessToken === null) {
            return $this->emptyAccountInsights($since, $until);
        }

        $userStats = $this->fetchUserStats($accessToken);
        $allVideos = $this->listAllVideos($accessToken);

        $currentVideos = $this->filterVideosByDateRange($allVideos, $since, $until);
        $currentAgg = $this->aggregateVideos($currentVideos);

        $sinceDt = Carbon::parse($since);
        $untilDt = Carbon::parse($until);
        $periodDays = $sinceDt->diffInDays($untilDt) + 1;
        $prevUntilDt = $sinceDt->copy()->subDay();
        $prevSinceDt = $prevUntilDt->copy()->subDays($periodDays - 1);

        $prevVideos = $this->filterVideosByDateRange(
            $allVideos,
            $prevSinceDt->format('Y-m-d'),
            $prevUntilDt->format('Y-m-d')
        );
        $prevAgg = $this->aggregateVideos($prevVideos);

        $payload = array_merge($userStats, $currentAgg, [
            'range' => ['since' => $since, 'until' => $until],
        ]);

        $periodMetrics = ['view_count', 'like_count', 'comment_count', 'share_count', 'videos_published'];
        $payload['comparison'] = [];
        foreach ($periodMetrics as $metric) {
            $curr = (float) ($payload[$metric] ?? 0);
            $prev = (float) ($prevAgg[$metric] ?? 0);
            $diff = $curr - $prev;
            if ($prev == 0.0) {
                $payload['comparison'][$metric] = [
                    'change' => $curr > 0 ? 100.0 : 0.0,
                    'direction' => $curr > 0 ? 'up' : null,
                    'diff' => $curr > 0 ? $diff : 0,
                ];
                continue;
            }

            $change = round((($curr - $prev) / $prev) * 100, 1);
            $payload['comparison'][$metric] = [
                'change' => $change,
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : null),
                'diff' => $diff,
            ];
        }

        return $payload;
    }

    /**
     * Public videos in range with TikTok-native post insight fields.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getVideosWithInsights(Tiktok $account, ?string $since, ?string $until): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);
        $accessToken = $this->resolveAccessToken($account);
        if ($accessToken === null) {
            return [];
        }

        $allVideos = $this->listAllVideos($accessToken);
        $inRange = $this->filterVideosByDateRange($allVideos, $since, $until);

        return array_map(fn (array $video) => $this->normalizeVideoToPost($video), $inRange);
    }

    /**
     * Fetch all public videos (paginated) for persistence.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listAllVideosForSync(Tiktok $account): array
    {
        $accessToken = $this->resolveAccessToken($account);
        if ($accessToken === null) {
            return [];
        }

        return $this->listAllVideos($accessToken);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function normalizeDateRange(?string $since, ?string $until): array
    {
        $until = $until ?: Carbon::today()->format('Y-m-d');
        $since = $since ?: Carbon::parse($until)->subDays(28)->format('Y-m-d');

        if ($since > $until) {
            $until = $since;
        }

        return [$since, $until];
    }

    /**
     * @return array<string, int>
     */
    protected function fetchUserStats(string $accessToken): array
    {
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        $response = $this->http->get(self::BASE_URL.'user/info/', [
            'fields' => 'follower_count,following_count,likes_count,video_count',
        ], $header);

        $user = $response['data']['user'] ?? [];

        return [
            'follower_count' => (int) ($user['follower_count'] ?? 0),
            'following_count' => (int) ($user['following_count'] ?? 0),
            'likes_count' => (int) ($user['likes_count'] ?? 0),
            'video_count' => (int) ($user['video_count'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function listAllVideos(string $accessToken): array
    {
        $header = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Authorization' => 'Bearer '.$accessToken,
        ];

        $endpoint = self::BASE_URL.'video/list/?fields='.urlencode(self::VIDEO_LIST_FIELDS);
        $videos = [];
        $cursor = null;
        $pages = 0;

        do {
            $body = ['max_count' => 20];
            if ($cursor !== null) {
                $body['cursor'] = $cursor;
            }

            $response = $this->http->postJson($endpoint, $body, $header);
            if (! is_array($response)) {
                break;
            }

            $errorCode = $response['error']['code'] ?? null;
            if ($errorCode !== null && $errorCode !== 'ok') {
                Log::warning('TikTok video list failed', [
                    'code' => $errorCode,
                    'message' => $response['error']['message'] ?? '',
                ]);
                break;
            }

            $batch = $response['data']['videos'] ?? [];
            if (! is_array($batch)) {
                break;
            }

            foreach ($batch as $row) {
                if (is_array($row)) {
                    $videos[] = $row;
                }
            }

            $hasMore = (bool) ($response['data']['has_more'] ?? false);
            $cursor = $response['data']['cursor'] ?? null;
            $pages++;

            if (count($videos) >= self::MAX_VIDEOS_PER_SYNC) {
                break;
            }
        } while ($hasMore && $cursor !== null && $pages < 15);

        return $videos;
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<int, array<string, mixed>>
     */
    protected function filterVideosByDateRange(array $videos, string $since, string $until): array
    {
        $sinceDt = Carbon::parse($since)->startOfDay();
        $untilDt = Carbon::parse($until)->endOfDay();

        return array_values(array_filter($videos, function (array $video) use ($sinceDt, $untilDt) {
            $created = $this->videoCreatedAt($video);
            if (! $created) {
                return false;
            }

            return $created->between($sinceDt, $untilDt);
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<string, mixed>
     */
    protected function aggregateVideos(array $videos): array
    {
        $totals = [
            'view_count' => 0,
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
            'videos_published' => count($videos),
            'view_count_by_day' => [],
            'like_count_by_day' => [],
            'comment_count_by_day' => [],
            'share_count_by_day' => [],
        ];

        foreach ($videos as $video) {
            $views = (int) ($video['view_count'] ?? 0);
            $likes = (int) ($video['like_count'] ?? 0);
            $comments = (int) ($video['comment_count'] ?? 0);
            $shares = (int) ($video['share_count'] ?? 0);

            $totals['view_count'] += $views;
            $totals['like_count'] += $likes;
            $totals['comment_count'] += $comments;
            $totals['share_count'] += $shares;

            $created = $this->videoCreatedAt($video);
            if (! $created) {
                continue;
            }
            $day = $created->format('Y-m-d');
            $totals['view_count_by_day'][$day] = ($totals['view_count_by_day'][$day] ?? 0) + $views;
            $totals['like_count_by_day'][$day] = ($totals['like_count_by_day'][$day] ?? 0) + $likes;
            $totals['comment_count_by_day'][$day] = ($totals['comment_count_by_day'][$day] ?? 0) + $comments;
            $totals['share_count_by_day'][$day] = ($totals['share_count_by_day'][$day] ?? 0) + $shares;
        }

        ksort($totals['view_count_by_day']);
        ksort($totals['like_count_by_day']);
        ksort($totals['comment_count_by_day']);
        ksort($totals['share_count_by_day']);

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $video
     * @return array<string, mixed>
     */
    public function normalizeVideoToPost(array $video): array
    {
        $created = $this->videoCreatedAt($video);
        $title = trim((string) ($video['title'] ?? ''));
        $description = trim((string) ($video['video_description'] ?? ''));
        $message = $title !== '' ? $title : $description;
        if ($title !== '' && $description !== '' && $description !== $title) {
            $message = $title."\n".$description;
        }

        $insights = [
            'view_count' => (int) ($video['view_count'] ?? 0),
            'like_count' => (int) ($video['like_count'] ?? 0),
            'comment_count' => (int) ($video['comment_count'] ?? 0),
            'share_count' => (int) ($video['share_count'] ?? 0),
        ];

        return [
            'id' => (string) ($video['id'] ?? ''),
            'created_time' => $created ? $created->toIso8601String() : null,
            'message' => $message,
            'title' => $title,
            'video_description' => $description,
            'full_picture' => $video['cover_image_url'] ?? null,
            'permalink_url' => $video['share_url'] ?? null,
            'share_url' => $video['share_url'] ?? null,
            'duration' => (int) ($video['duration'] ?? 0),
            'type' => 'video',
            'insights' => $insights,
        ];
    }

    /**
     * @param  array<string, mixed>  $video
     */
    protected function videoCreatedAt(array $video): ?Carbon
    {
        $ts = $video['create_time'] ?? null;
        if ($ts === null || $ts === '') {
            return null;
        }

        try {
            $seconds = (int) $ts;
            if ($seconds > 9999999999) {
                $seconds = (int) floor($seconds / 1000);
            }

            return Carbon::createFromTimestampUTC($seconds);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAccessToken(Tiktok $account): ?string
    {
        if (empty($account->access_token)) {
            return null;
        }

        $tokenResponse = TikTokService::validateToken($account);
        if (! ($tokenResponse['success'] ?? false)) {
            return null;
        }

        return (string) ($tokenResponse['access_token'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyAccountInsights(string $since, string $until): array
    {
        return [
            'follower_count' => 0,
            'following_count' => 0,
            'likes_count' => 0,
            'video_count' => 0,
            'view_count' => 0,
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
            'videos_published' => 0,
            'view_count_by_day' => [],
            'like_count_by_day' => [],
            'comment_count_by_day' => [],
            'share_count_by_day' => [],
            'comparison' => [],
            'range' => ['since' => $since, 'until' => $until],
        ];
    }
}
