<?php

namespace App\Services;

use App\Models\Board;
use App\Models\BoardInsight;
use App\Models\Pinterest;
use App\Models\PinterestPin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PinterestBoardAnalyticsService
{
    private const BASE_URL = 'https://api.pinterest.com/v5/';

    /** Max pins to list from the Pinterest API and to run pin analytics on per board sync. */
    private const MAX_BOARD_PINS = 100;

    private const REQUEST_DELAY_US = 100000;

    /** @var list<string> */
    private const PIN_ANALYTICS_METRICS = [
        'IMPRESSION',
        'SAVE',
        'OUTBOUND_CLICK',
        'PIN_CLICK',
        'VIDEO_MRC_VIEW',
        'TOTAL_COMMENTS',
        'TOTAL_REACTIONS',
    ];

    public function __construct(protected PinterestService $pinterestService) {}

    /**
     * Build insights + normalized pins, persist board_insights and pinterest_pins.
     *
     * @return array{insights: array<string, mixed>, pins: array<int, array<string, mixed>>}
     */
    public function syncBoard(Board $board, string $since, string $until, string $duration): array
    {
        $snapshot = $this->buildBoardAnalyticsSnapshot($board, $since, $until);
        $insights = $snapshot['insights'];
        $pins = $snapshot['pins'];

        BoardInsight::updateOrCreate(
            [
                'board_id' => $board->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'insights' => $insights,
                'synced_at' => now(),
            ]
        );

        PinterestPin::persistFromAnalyticsPins((int) $board->id, $pins);

        return ['insights' => $insights, 'pins' => $pins];
    }

    /**
     * @return array{insights: array<string, mixed>, pins: array<int, array<string, mixed>>}
     */
    public function buildBoardAnalyticsSnapshot(Board $board, string $since, string $until): array
    {
        $token = $this->resolveAccessToken($board);
        if ($token === null || empty($board->board_id)) {
            return [
                'insights' => $this->emptyInsightsPayload($since, $until),
                'pins' => [],
            ];
        }

        [$sinceClamped, $untilClamped] = $this->clampToPinterestAnalyticsWindow($since, $until);
        $pins = $this->listBoardPins($token, (string) $board->board_id);
        $clips = array_slice($pins, 0, self::MAX_BOARD_PINS);

        $aggCurrent = ['totals' => [], 'by_day' => []];
        $aggPrevious = ['totals' => [], 'by_day' => []];
        [$prevSince, $prevUntil] = $this->previousAlignedRange($sinceClamped, $untilClamped);

        $followers = $this->fetchUserFollowerCount($token);
        $normalized = [];

        foreach ($clips as $pin) {
            if (! is_array($pin)) {
                continue;
            }
            $pinId = (string) ($pin['id'] ?? '');
            if ($pinId === '') {
                continue;
            }

            $curBody = $this->fetchPinAnalytics($token, $pinId, $sinceClamped, $untilClamped);
            $this->mergeAnalyticsIntoAggregate($curBody, $aggCurrent);
            $prevBody = $this->fetchPinAnalytics($token, $pinId, $prevSince, $prevUntil);
            $this->mergeAnalyticsIntoAggregate($prevBody, $aggPrevious);

            $normalized[] = $this->buildNormalizedPin($pin, $this->extractSummaryTotals($curBody));

            usleep(self::REQUEST_DELAY_US);
        }

        $insights = $this->buildInsightsShape(
            $followers,
            $aggCurrent,
            $aggPrevious,
            $sinceClamped,
            $untilClamped
        );

        return ['insights' => $insights, 'pins' => $normalized];
    }

    public function resolveAccessToken(Board $board): ?string
    {
        $pinterest = $board->pinterest;
        if (! $pinterest instanceof Pinterest) {
            return null;
        }

        if (! $pinterest->validToken()) {
            if (empty($pinterest->refresh_token)) {
                return null;
            }
            $refreshed = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
            if (! ($refreshed['success'] ?? false)) {
                return null;
            }
            $pinterest->refresh();
        }

        $token = (string) ($pinterest->access_token ?? '');

        return $token !== '' ? $token : null;
    }

    private function fetchUserFollowerCount(string $accessToken): int
    {
        $data = $this->v5get('user_account', $accessToken, []);
        if (! is_array($data)) {
            return 0;
        }

        return (int) ($data['follower_count'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listBoardPins(string $accessToken, string $pinterestBoardId): array
    {
        $all = [];
        $bookmark = null;
        do {
            if (count($all) >= self::MAX_BOARD_PINS) {
                break;
            }
            $query = ['page_size' => 100];
            if ($bookmark) {
                $query['bookmark'] = $bookmark;
            }
            $response = $this->v5get('boards/'.rawurlencode($pinterestBoardId).'/pins', $accessToken, $query);
            if (! is_array($response)) {
                break;
            }
            $items = $response['items'] ?? [];
            if (is_array($items)) {
                foreach ($items as $row) {
                    if (count($all) >= self::MAX_BOARD_PINS) {
                        break 2;
                    }
                    if (is_array($row)) {
                        $all[] = $row;
                    }
                }
            }
            $bookmark = $response['bookmark'] ?? null;
            if (empty($bookmark) || count($all) >= self::MAX_BOARD_PINS) {
                break;
            }
            usleep(self::REQUEST_DELAY_US);
        } while (true);

        if (count($all) > self::MAX_BOARD_PINS) {
            $all = array_slice($all, 0, self::MAX_BOARD_PINS);
        }

        usort($all, static function (array $a, array $b): int {
            $ta = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
            $tb = strtotime((string) ($b['created_at'] ?? '')) ?: 0;

            return $tb <=> $ta;
        });

        return $all;
    }

    private function fetchPinAnalytics(string $accessToken, string $pinId, string $startDate, string $endDate): ?array
    {
        $query = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'metric_types' => implode(',', self::PIN_ANALYTICS_METRICS),
        ];

        return $this->v5get('pins/'.rawurlencode($pinId).'/analytics', $accessToken, $query);
    }

    private function v5get(string $path, string $accessToken, array $query): ?array
    {
        $url = self::BASE_URL.$path;
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(45)
            ->get($url, $query);

        if (! $response->successful()) {
            Log::warning('Pinterest v5 GET failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /**
     * @return array<string, float|int>
     */
    private function extractSummaryTotals(?array $body): array
    {
        $totals = [];
        if (! is_array($body)) {
            return $totals;
        }
        if (isset($body['summary_metrics']) || isset($body['daily_metrics'])) {
            $scratch = [];
            $this->accumulateSingleAnalyticsBlock($body, $totals, $scratch);

            return $totals;
        }
        foreach ($body as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (! isset($block['summary_metrics']) && ! isset($block['daily_metrics'])) {
                continue;
            }
            $scratch = [];
            $this->accumulateSingleAnalyticsBlock($block, $totals, $scratch);
        }

        return $totals;
    }

    /**
     * @param  array<string, float|int>  $totalsOut
     * @param  array<string, array<string, float|int>>  $byDayOut
     */
    private function accumulateSingleAnalyticsBlock(array $block, array &$totalsOut, array &$byDayOut): void
    {
        foreach ($block['summary_metrics'] ?? [] as $k => $v) {
            if (is_numeric($v)) {
                $key = (string) $k;
                $totalsOut[$key] = ($totalsOut[$key] ?? 0) + (float) $v;
            }
        }
        foreach ($block['daily_metrics'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $date = (string) ($row['date'] ?? '');
            if ($date === '') {
                continue;
            }
            $metrics = is_array($row['metrics'] ?? null) ? $row['metrics'] : [];
            foreach ($metrics as $mk => $mv) {
                if (! is_numeric($mv)) {
                    continue;
                }
                $mkStr = (string) $mk;
                $byDayOut[$date][$mkStr] = ($byDayOut[$date][$mkStr] ?? 0) + (float) $mv;
            }
        }
    }

    private function mergeAnalyticsIntoAggregate(?array $body, array &$aggregate): void
    {
        if (! is_array($body)) {
            return;
        }
        if (isset($body['summary_metrics']) || isset($body['daily_metrics'])) {
            $this->accumulateSingleAnalyticsBlock($body, $aggregate['totals'], $aggregate['by_day']);

            return;
        }
        foreach ($body as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (! isset($block['summary_metrics']) && ! isset($block['daily_metrics'])) {
                continue;
            }
            $this->accumulateSingleAnalyticsBlock($block, $aggregate['totals'], $aggregate['by_day']);
        }
    }

    /**
     * @param  array<string, float|int>  $pinTotals
     * @return array<string, mixed>
     */
    private function buildNormalizedPin(array $pinRow, array $pinTotals): array
    {
        $pinId = (string) ($pinRow['id'] ?? '');
        $createdRaw = (string) ($pinRow['created_at'] ?? '');
        $createdIso = $createdRaw;
        try {
            $createdIso = $createdRaw !== '' ? Carbon::parse($createdRaw)->toIso8601String() : '';
        } catch (\Throwable) {
        }

        $impressions = (int) round($pinTotals['IMPRESSION'] ?? 0);
        $saves = (int) round($pinTotals['SAVE'] ?? 0);
        $outbound = (int) round($pinTotals['OUTBOUND_CLICK'] ?? 0);
        $pinClicks = (int) round($pinTotals['PIN_CLICK'] ?? 0);
        $videoViews = (int) round($pinTotals['VIDEO_MRC_VIEW'] ?? 0);
        $comments = (int) round($pinTotals['TOTAL_COMMENTS'] ?? 0);
        $reactions = (int) round($pinTotals['TOTAL_REACTIONS'] ?? 0);

        $imageUrl = $this->pickPinImageUrl($pinRow);
        $title = (string) ($pinRow['title'] ?? '');
        $description = (string) ($pinRow['description'] ?? '');
        $link = (string) ($pinRow['link'] ?? '');
        $permalink = $pinId !== '' ? 'https://www.pinterest.com/pin/'.$pinId.'/' : $link;
        $mediaType = (string) ($pinRow['media']['media_type'] ?? $pinRow['type'] ?? '');

        return [
            'id' => $pinId,
            'title' => $title,
            'description' => $description,
            'message' => $description !== '' ? $description : $title,
            'link' => $link,
            'created_time' => $createdIso,
            'full_picture' => $imageUrl,
            'permalink_url' => $permalink,
            'type' => $mediaType,
            'media_type' => $mediaType,
            // Only metrics returned by Pinterest pin analytics API (`metric_types`); no derived aliases.
            'insights' => [
                'post_impressions' => $impressions,
                'pin_saves' => $saves,
                'outbound_clicks' => $outbound,
                'pin_clicks' => $pinClicks,
                'video_mrc_view' => $videoViews,
                'total_comments' => $comments,
                'total_reactions' => $reactions,
            ],
        ];
    }

    private function pickPinImageUrl(array $pinRow): string
    {
        $images = $pinRow['media']['images'] ?? null;
        if (is_array($images)) {
            foreach (['1200x', '564x', '736x', '600x315', '400x300', '150x150'] as $size) {
                if (! empty($images[$size]['url'])) {
                    return (string) $images[$size]['url'];
                }
            }
            foreach ($images as $img) {
                if (is_array($img) && ! empty($img['url'])) {
                    return (string) $img['url'];
                }
            }
        }

        return (string) ($pinRow['image_cover_url'] ?? $pinRow['image_cover_hd_url'] ?? '');
    }

    /**
     * @param  array{totals: array<string, float|int>, by_day: array<string, array<string, float|int>>}  $current
     * @param  array{totals: array<string, float|int>, by_day: array<string, array<string, float|int>>}  $previous
     * @return array<string, mixed>
     */
    private function buildInsightsShape(int $followers, array $current, array $previous, string $since, string $until): array
    {
        $t = $current['totals'];
        $reach = (int) round($t['IMPRESSION'] ?? 0);
        $videoViews = (int) round($t['VIDEO_MRC_VIEW'] ?? 0);
        $engagements = (int) round(
            ($t['SAVE'] ?? 0) + ($t['OUTBOUND_CLICK'] ?? 0) + ($t['PIN_CLICK'] ?? 0)
            + ($t['TOTAL_COMMENTS'] ?? 0) + ($t['TOTAL_REACTIONS'] ?? 0)
        );

        $reachByDay = [];
        $videoByDay = [];
        $engByDay = [];
        foreach ($current['by_day'] ?? [] as $date => $metrics) {
            if (! is_array($metrics)) {
                continue;
            }
            $reachByDay[$date] = (int) round($metrics['IMPRESSION'] ?? 0);
            $videoByDay[$date] = (int) round($metrics['VIDEO_MRC_VIEW'] ?? 0);
            $engByDay[$date] = (int) round(
                ($metrics['SAVE'] ?? 0) + ($metrics['OUTBOUND_CLICK'] ?? 0) + ($metrics['PIN_CLICK'] ?? 0)
                + ($metrics['TOTAL_COMMENTS'] ?? 0) + ($metrics['TOTAL_REACTIONS'] ?? 0)
            );
        }
        ksort($reachByDay);
        ksort($videoByDay);
        ksort($engByDay);

        $pt = $previous['totals'];
        $prevReach = (int) round($pt['IMPRESSION'] ?? 0);
        $prevVideo = (int) round($pt['VIDEO_MRC_VIEW'] ?? 0);
        $prevEng = (int) round(
            ($pt['SAVE'] ?? 0) + ($pt['OUTBOUND_CLICK'] ?? 0) + ($pt['PIN_CLICK'] ?? 0)
            + ($pt['TOTAL_COMMENTS'] ?? 0) + ($pt['TOTAL_REACTIONS'] ?? 0)
        );

        $metrics = ['followers', 'reach', 'video_views', 'engagements'];
        $currVals = [
            'followers' => (float) $followers,
            'reach' => (float) $reach,
            'video_views' => (float) $videoViews,
            'engagements' => (float) $engagements,
        ];
        $prevVals = [
            'followers' => (float) $followers,
            'reach' => (float) $prevReach,
            'video_views' => (float) $prevVideo,
            'engagements' => (float) $prevEng,
        ];

        $comparison = [];
        foreach ($metrics as $metric) {
            $curr = $currVals[$metric];
            $prev = $prevVals[$metric];
            $diff = $curr - $prev;
            if ($metric === 'followers') {
                $comparison[$metric] = [
                    'change' => 0.0,
                    'direction' => null,
                    'diff' => 0.0,
                ];
                continue;
            }
            if ($prev == 0.0) {
                $comparison[$metric] = [
                    'change' => $curr > 0 ? 100.0 : 0.0,
                    'direction' => $curr > 0 ? 'up' : null,
                    'diff' => $curr > 0 ? $diff : 0.0,
                ];
                continue;
            }
            $change = round((($curr - $prev) / $prev) * 100, 1);
            $comparison[$metric] = [
                'change' => $change,
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : null),
                'diff' => $diff,
            ];
        }

        return [
            'followers' => $followers,
            'reach' => $reach,
            'video_views' => $videoViews,
            'engagements' => $engagements,
            'followers_by_day' => [],
            'reach_by_day' => $reachByDay,
            'video_views_by_day' => $videoByDay,
            'engagements_by_day' => $engByDay,
            'comparison' => $comparison,
            'range' => ['since' => $since, 'until' => $until],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyInsightsPayload(string $since, string $until): array
    {
        return [
            'followers' => 0,
            'reach' => 0,
            'video_views' => 0,
            'engagements' => 0,
            'followers_by_day' => [],
            'reach_by_day' => [],
            'video_views_by_day' => [],
            'engagements_by_day' => [],
            'comparison' => [],
            'range' => ['since' => $since, 'until' => $until],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function clampToPinterestAnalyticsWindow(string $since, string $until): array
    {
        $untilDt = Carbon::parse($until)->startOfDay();
        $sinceDt = Carbon::parse($since)->startOfDay();
        $minSince = Carbon::today()->subDays(89);
        if ($sinceDt->lt($minSince)) {
            $sinceDt = $minSince->copy();
        }
        if ($sinceDt->gt($untilDt)) {
            $sinceDt = $untilDt->copy();
        }

        return [$sinceDt->format('Y-m-d'), $untilDt->format('Y-m-d')];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function previousAlignedRange(string $since, string $until): array
    {
        $sinceDt = Carbon::parse($since)->startOfDay();
        $untilDt = Carbon::parse($until)->startOfDay();
        $periodDays = $sinceDt->diffInDays($untilDt) + 1;
        $prevUntilDt = $sinceDt->copy()->subDay();
        $prevSinceDt = $prevUntilDt->copy()->subDays($periodDays - 1);
        $minSince = Carbon::today()->subDays(89);
        if ($prevSinceDt->lt($minSince)) {
            $prevSinceDt = $minSince->copy();
        }
        if ($prevSinceDt->gt($prevUntilDt)) {
            $prevSinceDt = $prevUntilDt->copy();
        }

        return [$prevSinceDt->format('Y-m-d'), $prevUntilDt->format('Y-m-d')];
    }
}
