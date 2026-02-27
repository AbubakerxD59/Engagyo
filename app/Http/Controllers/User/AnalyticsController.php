<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageInsight;
use App\Models\PagePost;
use App\Services\FacebookService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Resolve date range from duration preset or custom since/until.
     * Returns [since, until] as Y-m-d strings.
     */
    private function resolveDateRange(Request $request): array
    {
        $duration = $request->query('duration', 'last_28');
        $customSince = $request->query('since');
        $customUntil = $request->query('until');

        if ($duration === 'custom' && $customSince) {
            $since = Carbon::parse($customSince)->format('Y-m-d');
            $until = $customUntil ? Carbon::parse($customUntil)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            if ($since > $until) {
                $until = $since;
            }
            return [$since, $until];
        }

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
    public function __construct(
        protected FacebookService $facebookService
    ) {}

    /**
     * Display page-level analytics (insights).
     * Analytics data is loaded via AJAX when a page is selected.
     */
    public function index(Request $request)
    {
        $accounts = auth()->user()->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();

        $today = Carbon::today();
        $since = $today->copy()->subDays(28)->format('Y-m-d');
        $until = $today->format('Y-m-d');
        $duration = 'last_28';

        $selectedPage = null;
        return view('user.analytics.index', compact('facebookPages', 'selectedPage', 'since', 'until', 'duration'));
    }

    /**
     * Return page insights data as JSON for AJAX.
     */
    public function data(Request $request)
    {
        $accounts = auth()->user()->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();

        [$since, $until] = $this->resolveDateRange($request);

        $pageId = $request->query('page_id');
        $selectedPage = null;
        $pageInsights = null;
        $pagePosts = null;

        if ($pageId === 'all' || empty($pageId)) {
            $pageInsights = $this->fetchAggregatedInsights($facebookPages, $since, $until);
            $selectedPage = $facebookPages->count() > 0 ? ['id' => 'all', 'name' => 'All Accounts'] : null;
        } elseif ($facebookPages->contains('id', (int) $pageId)) {
            $selectedPage = Page::find($pageId);
            if ($selectedPage) {
                $pageInsights = $this->fetchPageInsights($selectedPage, $since, $until);
                $pagePosts = $this->fetchPagePosts($selectedPage, $since, $until);
                $selectedPage = ['id' => $selectedPage->id, 'name' => $selectedPage->name];
            }
        }

        return response()->json([
            'success' => true,
            'pageInsights' => $pageInsights,
            'pagePosts' => $pagePosts,
            'selectedPage' => $selectedPage,
            'hasPages' => $facebookPages->count() > 0,
            'since' => $since,
            'until' => $until,
        ]);
    }

    /**
     * Fetch page-level insights (followers, reach, video views, engagements).
     * Returns from DB if stored; fetches from Graph API only when no stored data.
     * Data is refreshed automatically by cronjobs.
     */
    private function fetchPageInsights(?Page $page, ?string $since = null, ?string $until = null): ?array
    {
        if (!$page || empty($page->page_id) || empty($page->access_token)) {
            return null;
        }

        $duration = request()->query('duration', 'last_28');

        $stored = PageInsight::where('page_id', $page->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();

        if ($stored && $stored->insights) {
            return $stored->insights;
        }

        $tokenCheck = FacebookService::validateToken($page);
        if (!$tokenCheck['success']) {
            return null;
        }

        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;
        $insights = $this->facebookService->getPageInsightsWithComparison($page->page_id, $accessToken, $since, $until);

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

        return $insights;
    }

    /**
     * Fetch page posts with insights. Returns from DB if stored; fetches from Graph API only when no stored data.
     * Returns null for "All Accounts" (posts not aggregated across pages).
     * Data is refreshed automatically by cronjobs.
     */
    private function fetchPagePosts(?Page $page, ?string $since = null, ?string $until = null): ?array
    {
        if (!$page || empty($page->page_id) || empty($page->access_token)) {
            return null;
        }

        $duration = request()->query('duration', 'last_28');

        $stored = PagePost::where('page_id', $page->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();

        if ($stored && $stored->posts !== null) {
            return $stored->posts;
        }

        $tokenCheck = FacebookService::validateToken($page);
        if (!$tokenCheck['success']) {
            return null;
        }

        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;
        dd('here');
        $posts = $this->facebookService->getPagePostsWithInsights($page->page_id, $accessToken, $since, $until);

        PagePost::updateOrCreate(
            [
                'page_id' => $page->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'posts' => $posts,
                'synced_at' => now(),
            ]
        );

        return $posts;
    }

    /**
     * Fetch aggregated insights for all Facebook pages.
     * Sums followers, reach, video_views, engagements and merges engagements_by_day per date.
     */
    private function fetchAggregatedInsights($facebookPages, ?string $since, ?string $until): ?array
    {
        if ($facebookPages->isEmpty()) {
            return null;
        }

        $aggregated = [
            'followers' => 0,
            'reach' => 0,
            'video_views' => 0,
            'engagements' => 0,
            'followers_by_day' => [],
            'reach_by_day' => [],
            'video_views_by_day' => [],
            'engagements_by_day' => [],
            'comparison' => [],
        ];

        $hasAnyData = false;
        foreach ($facebookPages as $page) {
            $insights = $this->fetchPageInsights($page, $since, $until);
            if (!$insights) {
                continue;
            }
            if (is_numeric($insights['followers'] ?? null)) {
                $aggregated['followers'] += (int) $insights['followers'];
                $hasAnyData = true;
            }
            if (is_numeric($insights['reach'] ?? null)) {
                $aggregated['reach'] += (int) $insights['reach'];
                $hasAnyData = true;
            }
            if (is_numeric($insights['video_views'] ?? null)) {
                $aggregated['video_views'] += (int) $insights['video_views'];
                $hasAnyData = true;
            }
            if (is_numeric($insights['engagements'] ?? null)) {
                $aggregated['engagements'] += (int) $insights['engagements'];
                $hasAnyData = true;
            }
            foreach ($insights['engagements_by_day'] ?? [] as $date => $val) {
                $aggregated['engagements_by_day'][$date] = ($aggregated['engagements_by_day'][$date] ?? 0) + (int) $val;
            }
            foreach ($insights['followers_by_day'] ?? [] as $date => $val) {
                $aggregated['followers_by_day'][$date] = ($aggregated['followers_by_day'][$date] ?? 0) + (int) $val;
            }
            foreach ($insights['reach_by_day'] ?? [] as $date => $val) {
                $aggregated['reach_by_day'][$date] = ($aggregated['reach_by_day'][$date] ?? 0) + (int) $val;
            }
            foreach ($insights['video_views_by_day'] ?? [] as $date => $val) {
                $aggregated['video_views_by_day'][$date] = ($aggregated['video_views_by_day'][$date] ?? 0) + (int) $val;
            }
        }

        ksort($aggregated['followers_by_day']);
        ksort($aggregated['reach_by_day']);
        ksort($aggregated['video_views_by_day']);
        ksort($aggregated['engagements_by_day']);

        $until = $until ?: now()->format('Y-m-d');
        $since = $since ?: date('Y-m-d', strtotime('-28 days', strtotime($until)));
        $sinceDt = Carbon::parse($since);
        $untilDt = Carbon::parse($until);
        $periodDays = $sinceDt->diffInDays($untilDt) + 1;
        $prevUntilDt = $sinceDt->copy()->subDay();
        $prevSinceDt = $prevUntilDt->copy()->subDays($periodDays - 1);
        $prevSince = $prevSinceDt->format('Y-m-d');
        $prevUntil = $prevUntilDt->format('Y-m-d');

        $prevAggregated = [
            'followers' => 0,
            'reach' => 0,
            'video_views' => 0,
            'engagements' => 0,
        ];
        foreach ($facebookPages as $page) {
            $insights = $this->fetchPageInsights($page, $prevSince, $prevUntil);
            if (!$insights) {
                continue;
            }
            if (is_numeric($insights['followers'] ?? null)) {
                $prevAggregated['followers'] += (int) $insights['followers'];
            }
            if (is_numeric($insights['reach'] ?? null)) {
                $prevAggregated['reach'] += (int) $insights['reach'];
            }
            if (is_numeric($insights['video_views'] ?? null)) {
                $prevAggregated['video_views'] += (int) $insights['video_views'];
            }
            if (is_numeric($insights['engagements'] ?? null)) {
                $prevAggregated['engagements'] += (int) $insights['engagements'];
            }
        }

        $metrics = ['followers', 'reach', 'video_views', 'engagements'];
        foreach ($metrics as $metric) {
            $curr = $aggregated[$metric] ?? 0;
            $prev = $prevAggregated[$metric] ?? 0;
            $aggregated['comparison'][$metric] = ['change' => null, 'direction' => null, 'diff' => null];
            if (!is_numeric($curr) || !is_numeric($prev)) {
                continue;
            }
            $curr = (float) $curr;
            $prev = (float) $prev;
            $diff = $curr - $prev;
            if ($prev == 0) {
                $aggregated['comparison'][$metric] = [
                    'change' => $curr > 0 ? 100 : 0,
                    'direction' => $curr > 0 ? 'up' : null,
                    'diff' => $curr > 0 ? $diff : 0,
                ];
            } else {
                $change = round((($curr - $prev) / $prev) * 100, 1);
                $aggregated['comparison'][$metric] = [
                    'change' => $change,
                    'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : null),
                    'diff' => $diff,
                ];
            }
        }

        if (!$hasAnyData && empty($aggregated['engagements_by_day'])) {
            return null;
        }

        return $aggregated;
    }

    /**
     * Test page insights - displays formatted data and raw API response.
     * Route: GET panel/analytics/test?page_id={id}
     */
    public function testPageInsights(Request $request)
    {
        $accounts = auth()->user()->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();

        [$since, $until] = $this->resolveDateRange($request);
        $pageId = $request->query('page_id');

        $selectedPage = null;
        $pageInsights = null;
        $pagePosts = null;
        $apiResponse = null;

        if ($pageId && $facebookPages->contains('id', (int) $pageId)) {
            $selectedPage = Page::find($pageId);
            if ($selectedPage) {
                $pageInsights = $this->fetchPageInsights($selectedPage, $since, $until);
                $pagePosts = $this->fetchPagePosts($selectedPage, $since, $until);
                $apiResponse = [
                    'success' => true,
                    'pageInsights' => $pageInsights,
                    'pagePosts' => $pagePosts,
                    'selectedPage' => $selectedPage ? ['id' => $selectedPage->id, 'name' => $selectedPage->name] : null,
                    'since' => $since,
                    'until' => $until,
                ];
            }
        } elseif ($pageId) {
            $apiResponse = ['success' => false, 'error' => 'Page not found or not owned by user.'];
        }

        return view('user.analytics.test', compact('facebookPages', 'pageId', 'selectedPage', 'pageInsights', 'pagePosts', 'apiResponse', 'since', 'until'));
    }
}
