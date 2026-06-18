<?php

namespace App\Services;

use App\Models\Youtube;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class YouTubeAnalyticsService
{
    private const MAX_VIDEOS_PER_SYNC = 100;

    public function __construct(protected HttpService $http) {}

    /**
     * Fetch channel videos (Data API) enriched with Analytics API metrics when available.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listAllVideosForSync(Youtube $account): array
    {
        $accessToken = $this->resolveAccessToken($account);
        if ($accessToken === null) {
            return [];
        }

        $channelId = trim((string) $account->channel_id);
        if ($channelId === '') {
            return [];
        }

        $uploadsPlaylistId = $this->resolveUploadsPlaylistId($accessToken, $channelId);
        if ($uploadsPlaylistId === '') {
            return [];
        }

        $videoIds = $this->collectPlaylistVideoIds($accessToken, $uploadsPlaylistId);
        if ($videoIds === []) {
            return [];
        }

        $videos = $this->fetchVideosByIds($accessToken, $videoIds);
        $since = Carbon::today()->subYear()->format('Y-m-d');
        $until = Carbon::today()->format('Y-m-d');
        $analyticsByVideo = $this->fetchVideoAnalyticsMap($accessToken, $channelId, $since, $until);

        $normalized = [];
        foreach ($videos as $video) {
            if (! is_array($video)) {
                continue;
            }
            $videoId = (string) ($video['id'] ?? '');
            $normalized[] = $this->normalizeVideoToPost($video, $analyticsByVideo[$videoId] ?? []);
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVideosWithInsights(Youtube $account, ?string $since, ?string $until): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);
        $accessToken = $this->resolveAccessToken($account);
        if ($accessToken === null) {
            return [];
        }

        $channelId = trim((string) $account->channel_id);
        if ($channelId === '') {
            return [];
        }

        $allVideos = $this->listAllVideosForSync($account);

        return array_values(array_filter($allVideos, function (array $post) use ($since, $until) {
            if (empty($post['created_time'])) {
                return false;
            }

            try {
                $created = Carbon::parse((string) $post['created_time']);

                return $created->between(
                    Carbon::parse($since)->startOfDay(),
                    Carbon::parse($until)->endOfDay()
                );
            } catch (\Throwable) {
                return false;
            }
        }));
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountInsightsWithComparison(Youtube $account, ?string $since, ?string $until): array
    {
        [$since, $until] = $this->normalizeDateRange($since, $until);
        $accessToken = $this->resolveAccessToken($account);
        if ($accessToken === null) {
            return $this->emptyAccountInsights($since, $until);
        }

        $channelId = trim((string) $account->channel_id);
        if ($channelId === '') {
            return $this->emptyAccountInsights($since, $until);
        }

        $current = $this->fetchChannelTotals($accessToken, $channelId, $since, $until);
        $channelStats = $this->fetchChannelStatistics($accessToken, $channelId);

        $sinceDt = Carbon::parse($since);
        $untilDt = Carbon::parse($until);
        $periodDays = $sinceDt->diffInDays($untilDt) + 1;
        $prevUntilDt = $sinceDt->copy()->subDay();
        $prevSinceDt = $prevUntilDt->copy()->subDays($periodDays - 1);
        $previous = $this->fetchChannelTotals(
            $accessToken,
            $channelId,
            $prevSinceDt->format('Y-m-d'),
            $prevUntilDt->format('Y-m-d')
        );

        $payload = array_merge($channelStats, $current, [
            'range' => ['since' => $since, 'until' => $until],
        ]);

        $periodMetrics = ['view_count', 'like_count', 'comment_count', 'share_count', 'estimated_minutes_watched', 'videos_published'];
        $payload['comparison'] = [];
        foreach ($periodMetrics as $metric) {
            $curr = (float) ($payload[$metric] ?? 0);
            $prev = (float) ($previous[$metric] ?? 0);
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
     * @param  array<string, mixed>  $video
     * @param  array<string, mixed>  $analytics
     * @return array<string, mixed>
     */
    public function normalizeVideoToPost(array $video, array $analytics = []): array
    {
        $videoId = (string) ($video['id'] ?? '');
        $snippet = is_array($video['snippet'] ?? null) ? $video['snippet'] : [];
        $statistics = is_array($video['statistics'] ?? null) ? $video['statistics'] : [];

        // Data API statistics are lifetime totals; Analytics API reports are period-scoped.
        $views = (int) ($statistics['viewCount'] ?? 0);
        $likes = (int) ($statistics['likeCount'] ?? 0);
        $comments = (int) ($statistics['commentCount'] ?? 0);
        $shares = (int) ($analytics['share_count'] ?? 0);
        $minutesWatched = (int) ($analytics['estimated_minutes_watched'] ?? 0);

        $createdTime = $snippet['publishedAt'] ?? null;
        $title = trim((string) ($snippet['title'] ?? ''));
        $description = trim((string) ($snippet['description'] ?? ''));

        $thumbnails = is_array($snippet['thumbnails'] ?? null) ? $snippet['thumbnails'] : [];
        $thumbnail = $thumbnails['high']['url']
            ?? $thumbnails['medium']['url']
            ?? $thumbnails['default']['url']
            ?? ($videoId !== '' ? 'https://img.youtube.com/vi/'.rawurlencode($videoId).'/hqdefault.jpg' : '');

        $insights = [
            'view_count' => $views,
            'like_count' => $likes,
            'comment_count' => $comments,
            'share_count' => $shares,
            'estimated_minutes_watched' => $minutesWatched,
            'average_view_duration' => (int) ($analytics['average_view_duration'] ?? 0),
            'post_reactions' => $likes,
            'post_impressions' => $views,
            'post_clicks' => 0,
        ];

        return [
            'id' => $videoId,
            'created_time' => $createdTime,
            'message' => $description !== '' ? ($title !== '' ? $title."\n\n".$description : $description) : $title,
            'title' => $title,
            'type' => 'video',
            'media_type' => 'video',
            'full_picture' => $thumbnail,
            'permalink_url' => $videoId !== ''
                ? 'https://www.youtube.com/watch?v='.rawurlencode($videoId)
                : null,
            'insights' => $insights,
        ];
    }

    protected function resolveAccessToken(Youtube $account): ?string
    {
        $tokenResponse = YouTubeService::validateToken($account);
        if (! ($tokenResponse['success'] ?? false)) {
            return null;
        }

        return (string) ($tokenResponse['access_token'] ?? '');
    }

    protected function resolveUploadsPlaylistId(string $accessToken, string $channelId): string
    {
        $response = $this->http->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'contentDetails',
            'id' => $channelId,
        ], [
            'Authorization' => 'Bearer '.$accessToken,
        ]);

        if (! is_array($response)) {
            return '';
        }

        return (string) ($response['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? '');
    }

    /**
     * @return list<string>
     */
    protected function collectPlaylistVideoIds(string $accessToken, string $playlistId): array
    {
        $ids = [];
        $pageToken = null;

        do {
            $query = [
                'part' => 'contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => 50,
            ];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->http->get('https://www.googleapis.com/youtube/v3/playlistItems', $query, [
                'Authorization' => 'Bearer '.$accessToken,
            ]);

            if (! is_array($response)) {
                break;
            }

            foreach ($response['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $videoId = (string) ($item['contentDetails']['videoId'] ?? '');
                if ($videoId !== '') {
                    $ids[] = $videoId;
                }
            }

            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken && count($ids) < self::MAX_VIDEOS_PER_SYNC);

        return array_slice(array_values(array_unique($ids)), 0, self::MAX_VIDEOS_PER_SYNC);
    }

    /**
     * @param  list<string>  $videoIds
     * @return list<array<string, mixed>>
     */
    protected function fetchVideosByIds(string $accessToken, array $videoIds): array
    {
        $videos = [];

        foreach (array_chunk($videoIds, 50) as $chunk) {
            $response = $this->http->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet,statistics,contentDetails',
                'id' => implode(',', $chunk),
            ], [
                'Authorization' => 'Bearer '.$accessToken,
            ]);

            if (! is_array($response)) {
                continue;
            }

            foreach ($response['items'] ?? [] as $item) {
                if (is_array($item)) {
                    $videos[] = $item;
                }
            }
        }

        return $videos;
    }

    /**
     * @return array<string, array<string, int>>
     */
    protected function fetchVideoAnalyticsMap(string $accessToken, string $channelId, string $since, string $until): array
    {
        $response = $this->http->get('https://youtubeanalytics.googleapis.com/v2/reports', [
            'ids' => 'channel=='.$channelId,
            'startDate' => $since,
            'endDate' => $until,
            'metrics' => 'views,likes,comments,shares,estimatedMinutesWatched,averageViewDuration',
            'dimensions' => 'video',
            'sort' => '-views',
            'maxResults' => self::MAX_VIDEOS_PER_SYNC,
        ], [
            'Authorization' => 'Bearer '.$accessToken,
        ]);

        if (! is_array($response)) {
            return [];
        }

        $headers = [];
        foreach ($response['columnHeaders'] ?? [] as $index => $header) {
            if (! is_array($header)) {
                continue;
            }
            $headers[$index] = (string) ($header['name'] ?? '');
        }

        $map = [];
        foreach ($response['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $videoId = (string) ($row[0] ?? '');
            if ($videoId === '') {
                continue;
            }

            $metrics = [];
            foreach ($headers as $index => $name) {
                if ($index === 0 || $name === '') {
                    continue;
                }
                $metrics[$name] = $row[$index] ?? 0;
            }

            $map[$videoId] = [
                'view_count' => (int) ($metrics['views'] ?? 0),
                'like_count' => (int) ($metrics['likes'] ?? 0),
                'comment_count' => (int) ($metrics['comments'] ?? 0),
                'share_count' => (int) ($metrics['shares'] ?? 0),
                'estimated_minutes_watched' => (int) ($metrics['estimatedMinutesWatched'] ?? 0),
                'average_view_duration' => (int) ($metrics['averageViewDuration'] ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @return array<string, int|float>
     */
    protected function fetchChannelTotals(string $accessToken, string $channelId, string $since, string $until): array
    {
        $response = $this->http->get('https://youtubeanalytics.googleapis.com/v2/reports', [
            'ids' => 'channel=='.$channelId,
            'startDate' => $since,
            'endDate' => $until,
            'metrics' => 'views,likes,comments,shares,estimatedMinutesWatched',
        ], [
            'Authorization' => 'Bearer '.$accessToken,
        ]);

        if (! is_array($response) || empty($response['rows'][0]) || ! is_array($response['rows'][0])) {
            return $this->emptyPeriodTotals();
        }

        $headers = [];
        foreach ($response['columnHeaders'] ?? [] as $index => $header) {
            if (! is_array($header)) {
                continue;
            }
            $headers[$index] = (string) ($header['name'] ?? '');
        }

        $row = $response['rows'][0];
        $metrics = [];
        foreach ($headers as $index => $name) {
            if ($name === '') {
                continue;
            }
            $metrics[$name] = $row[$index] ?? 0;
        }

        $videosInRange = $this->countVideosPublishedInRange($accessToken, $channelId, $since, $until);

        return [
            'view_count' => (int) ($metrics['views'] ?? 0),
            'like_count' => (int) ($metrics['likes'] ?? 0),
            'comment_count' => (int) ($metrics['comments'] ?? 0),
            'share_count' => (int) ($metrics['shares'] ?? 0),
            'estimated_minutes_watched' => (int) ($metrics['estimatedMinutesWatched'] ?? 0),
            'videos_published' => $videosInRange,
        ];
    }

    protected function countVideosPublishedInRange(string $accessToken, string $channelId, string $since, string $until): int
    {
        $response = $this->http->get('https://youtubeanalytics.googleapis.com/v2/reports', [
            'ids' => 'channel=='.$channelId,
            'startDate' => $since,
            'endDate' => $until,
            'metrics' => 'views',
            'dimensions' => 'video',
            'maxResults' => self::MAX_VIDEOS_PER_SYNC,
        ], [
            'Authorization' => 'Bearer '.$accessToken,
        ]);

        if (! is_array($response)) {
            return 0;
        }

        return count($response['rows'] ?? []);
    }

    /**
     * @return array<string, int>
     */
    protected function fetchChannelStatistics(string $accessToken, string $channelId): array
    {
        $response = $this->http->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'statistics',
            'id' => $channelId,
        ], [
            'Authorization' => 'Bearer '.$accessToken,
        ]);

        if (! is_array($response)) {
            return [
                'subscriber_count' => 0,
                'video_count' => 0,
                'view_count_total' => 0,
            ];
        }

        $statistics = is_array($response['items'][0]['statistics'] ?? null)
            ? $response['items'][0]['statistics']
            : [];

        return [
            'subscriber_count' => (int) ($statistics['subscriberCount'] ?? 0),
            'video_count' => (int) ($statistics['videoCount'] ?? 0),
            'view_count_total' => (int) ($statistics['viewCount'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyAccountInsights(string $since, string $until): array
    {
        return array_merge($this->emptyPeriodTotals(), [
            'subscriber_count' => 0,
            'video_count' => 0,
            'view_count_total' => 0,
            'range' => ['since' => $since, 'until' => $until],
            'comparison' => [],
        ]);
    }

    /**
     * @return array<string, int>
     */
    protected function emptyPeriodTotals(): array
    {
        return [
            'view_count' => 0,
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
            'estimated_minutes_watched' => 0,
            'videos_published' => 0,
        ];
    }
}
