<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageInsight;
use App\Models\PagePost;
use App\Models\Thread;
use App\Models\ThreadInsight;
use App\Models\ThreadPost;
use App\Models\User;
use App\Services\FacebookService;
use App\Services\ThreadsAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    private const POSTS_CACHE_TTL_HOURS = 3;

    /**
     * Normalize duration aliases used by different screens/endpoints.
     */
    private function normalizeDuration(?string $duration): string
    {
        $duration = (string) ($duration ?? 'last_28');

        return match ($duration) {
            'last_7_days' => 'last_7',
            'last_28_days' => 'last_28',
            'last_90_days' => 'last_90',
            'last_year' => 'full_year',
            default => $duration,
        };
    }

    /**
     * Resolve date range from duration preset or custom since/until.
     * Returns [since, until] as Y-m-d strings.
     */
    private function resolveDateRange(Request $request): array
    {
        $duration = $this->normalizeDuration($request->query('duration', 'last_28'));
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
            'full_year' => [
                $today->copy()->subYear()->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            default => [$today->copy()->subDays(28)->format('Y-m-d'), $today->format('Y-m-d')],
        };
    }
    public function __construct(
        protected FacebookService $facebookService,
        protected ThreadsAnalyticsService $threadsAnalyticsService
    ) {}

    /**
     * Display page-level analytics (insights).
     * Analytics data is loaded via AJAX when a page is selected.
     */
    public function index(Request $request)
    {
        $user = \App\Models\User::with('timezone')->find(auth()->id());
        if (!$user) {
            abort(403);
        }
        $accounts = $user->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();
        $threadsAccounts = $accounts->where('type', 'threads')->values();
        $analyticsAccounts = $facebookPages->map(function ($page) {
            return [
                'ref' => 'facebook:'.$page->id,
                'platform' => 'facebook',
                'id' => $page->id,
                'name' => $page->name,
                'username' => $page->facebook?->username ?? '',
                'profile_image' => $page->profile_image ?? social_logo('facebook'),
            ];
        })->values()->concat(
            $threadsAccounts->map(function ($thread) {
                return [
                    'ref' => 'threads:'.$thread->id,
                    'platform' => 'threads',
                    'id' => $thread->id,
                    'name' => $thread->username,
                    'username' => $thread->username,
                    'profile_image' => $thread->profile_image ?? social_logo('threads'),
                ];
            })->values()
        )->values();
        $userTimezoneName = $user->timezone && !empty($user->timezone->name) ? $user->timezone->name : 'UTC';

        $today = Carbon::today();
        $since = $today->copy()->subDays(28)->format('Y-m-d');
        $until = $today->format('Y-m-d');
        $duration = 'last_28';

        $selectedPage = null;
        return view('user.analytics.index', compact('facebookPages', 'threadsAccounts', 'analyticsAccounts', 'selectedPage', 'since', 'until', 'duration', 'userTimezoneName'));
    }

    /**
     * Return page insights data as JSON for AJAX.
     */
    public function data(Request $request)
    {
        $user = User::find(auth()->id());
        if (! $user) {
            return response()->json(['success' => false]);
        }
        $accounts = $user->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();
        $threadsAccounts = $accounts->where('type', 'threads')->values();

        [$since, $until] = $this->resolveDateRange($request);

        $accountRef = (string) $request->query('account_ref', '');
        $pageId = $request->query('page_id');
        $selectedPage = null;
        $pageInsights = null;
        $pagePosts = null;
        $platform = 'facebook';

        if ($accountRef === '' && ($pageId === 'all' || empty($pageId))) {
            $accountRef = 'facebook:all';
        } elseif ($accountRef === '' && ! empty($pageId)) {
            $accountRef = 'facebook:'.$pageId;
        }

        if ($accountRef === 'facebook:all') {
            $platform = 'facebook';
            $pageInsights = $this->fetchAggregatedInsights($facebookPages, $since, $until);
            $selectedPage = $facebookPages->count() > 0 ? ['id' => 'all', 'name' => 'All Facebook Pages'] : null;
        } elseif (str_starts_with($accountRef, 'facebook:')) {
            $platform = 'facebook';
            $id = (int) str_replace('facebook:', '', $accountRef);
            if ($facebookPages->contains('id', $id)) {
                $selected = Page::find($id);
                if ($selected) {
                    $pageInsights = $this->fetchPageInsights($selected, $since, $until);
                    $pagePosts = $this->fetchPagePosts($selected, $since, $until);
                    $selectedPage = ['id' => $selected->id, 'name' => $selected->name];
                }
            }
        } elseif (str_starts_with($accountRef, 'threads:')) {
            $platform = 'threads';
            $id = (int) str_replace('threads:', '', $accountRef);
            if ($threadsAccounts->contains('id', $id)) {
                $selected = Thread::find($id);
                if ($selected) {
                    $pageInsights = $this->fetchThreadInsights($selected, $since, $until);
                    $pagePosts = $this->fetchThreadPosts($selected, $since, $until);
                    $selectedPage = ['id' => $selected->id, 'name' => $selected->username];
                }
            }
        }

        return response()->json([
            'success' => true,
            'pageInsights' => $pageInsights,
            'pagePosts' => $pagePosts,
            'selectedPage' => $selectedPage,
            'hasPages' => ($facebookPages->count() + $threadsAccounts->count()) > 0,
            'platform' => $platform,
            'accountRef' => $accountRef,
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

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));

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

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));
        $cacheKey = $this->analyticsPostsCacheKey((int) auth()->id(), (int) $page->id, $duration, $since, $until);

        $cachedPosts = Cache::get($cacheKey);
        if ($cachedPosts !== null) {
            return $cachedPosts;
        }

        $stored = PagePost::where('page_id', $page->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();

        if ($stored && $stored->posts !== null) {
            Cache::put($cacheKey, $stored->posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
            return $stored->posts;
        }

        $tokenCheck = FacebookService::validateToken($page);
        if (!$tokenCheck['success']) {
            return null;
        }

        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;
        $insightsPreset = $duration === 'full_year' ? 'sent_tab' : 'default';
        $posts = $this->facebookService->getPagePostsWithInsights($page->page_id, $accessToken, $since, $until, $insightsPreset);

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

        Cache::put($cacheKey, $posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));

        return $posts;
    }

    /**
     * Fetch Threads account insights.
     */
    private function fetchThreadInsights(?Thread $thread, ?string $since = null, ?string $until = null): ?array
    {
        if (! $thread || empty($thread->threads_id) || empty($thread->access_token)) {
            return null;
        }

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));
        $stored = ThreadInsight::where('thread_id', $thread->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();
        if ($stored && $stored->insights) {
            return $stored->insights;
        }

        if (! $thread->validToken()) {
            return null;
        }

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

        return $insights;
    }

    /**
     * Fetch Threads posts with insights.
     */
    private function fetchThreadPosts(?Thread $thread, ?string $since = null, ?string $until = null): ?array
    {
        if (! $thread || empty($thread->threads_id) || empty($thread->access_token)) {
            return null;
        }

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));
        $cacheKey = $this->analyticsPostsCacheKey((int) auth()->id(), (int) $thread->id, $duration, $since, $until, 'threads');

        $cachedPosts = Cache::get($cacheKey);
        if ($cachedPosts !== null) {
            return $cachedPosts;
        }

        $stored = ThreadPost::where('thread_id', $thread->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();
        if ($stored && $stored->posts !== null) {
            Cache::put($cacheKey, $stored->posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
            return $stored->posts;
        }

        if (! $thread->validToken()) {
            return null;
        }

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

        Cache::put($cacheKey, $posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));

        return $posts;
    }

    private function analyticsPostsCacheKey(int $userId, int $accountId, string $duration, ?string $since, ?string $until, string $platform = 'facebook'): string
    {
        return implode(':', [
            'analytics_posts',
            'v2',
            'user',
            $userId,
            'platform',
            $platform,
            'account',
            $accountId,
            'duration',
            $duration,
            'since',
            (string) ($since ?? ''),
            'until',
            (string) ($until ?? ''),
        ]);
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
     * Fetch page posts for analytics test endpoint with raw status payload.
     */
    private function fetchPagePostsForTest(?Page $page, ?string $since = null, ?string $until = null): array
    {
        if (!$page || empty($page->page_id) || empty($page->access_token)) {
            return [
                'success' => false,
                'source' => 'validation',
                'posts' => null,
                'error' => 'Page not found or missing Facebook credentials.',
            ];
        }

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));

        $tokenCheck = FacebookService::validateToken($page);
        if (!$tokenCheck['success']) {
            return [
                'success' => false,
                'source' => 'token_validation',
                'posts' => null,
                'error' => $tokenCheck['message'] ?? 'Invalid or expired access token.',
            ];
        }

        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;
        $insightsPreset = $duration === 'full_year' ? 'sent_tab' : 'default';

        try {
            $posts = $this->facebookService->getPagePostsWithInsights($page->page_id, $accessToken, $since, $until, $insightsPreset);
        } catch (\Throwable $e) {
            if ($this->isFacebookReduceDataError($e)) {
                try {
                    $posts = $this->fetchPagePostsInChunksForTest($page->page_id, $accessToken, $since, $until, $insightsPreset, 30);
                    return [
                        'success' => true,
                        'source' => 'api_live_chunked',
                        'posts' => $posts,
                        'error' => null,
                    ];
                } catch (\Throwable $chunkError) {
                    return [
                        'success' => false,
                        'source' => 'api',
                        'posts' => null,
                        'error' => $chunkError->getMessage(),
                    ];
                }
            }

            return [
                'success' => false,
                'source' => 'api',
                'posts' => null,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'source' => 'api_live',
            'posts' => $posts,
            'error' => null,
        ];
    }

    private function isFacebookReduceDataError(\Throwable $e): bool
    {
        return str_contains(strtolower($e->getMessage()), "reduce the amount of data you're asking for");
    }

    private function fetchPagePostsInChunksForTest(string $pageId, string $accessToken, ?string $since, ?string $until, string $insightsPreset, int $chunkDays = 30): array
    {
        $safeUntil = $until ?: Carbon::today()->format('Y-m-d');
        $safeSince = $since ?: Carbon::parse($safeUntil)->subDays(28)->format('Y-m-d');

        $currentStart = Carbon::parse($safeSince)->startOfDay();
        $end = Carbon::parse($safeUntil)->endOfDay();
        $mergedById = [];

        while ($currentStart->lte($end)) {
            $chunkStart = $currentStart->copy();
            $chunkEnd = $chunkStart->copy()->addDays($chunkDays - 1)->endOfDay();
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunkPosts = $this->facebookService->getPagePostsWithInsights(
                $pageId,
                $accessToken,
                $chunkStart->format('Y-m-d'),
                $chunkEnd->format('Y-m-d'),
                $insightsPreset
            );

            foreach ($chunkPosts as $post) {
                $postId = (string) ($post['id'] ?? $post['post_id'] ?? '');
                if ($postId === '') {
                    $mergedById[] = $post;
                    continue;
                }
                $mergedById[$postId] = $post;
            }

            $currentStart = $chunkEnd->copy()->addDay()->startOfDay();
        }

        $posts = array_values($mergedById);
        usort($posts, function (array $a, array $b) {
            $aTimeRaw = $a['created_time'] ?? null;
            $bTimeRaw = $b['created_time'] ?? null;
            $aTime = is_array($aTimeRaw) ? ($aTimeRaw['date'] ?? $aTimeRaw['datetime'] ?? null) : $aTimeRaw;
            $bTime = is_array($bTimeRaw) ? ($bTimeRaw['date'] ?? $bTimeRaw['datetime'] ?? null) : $bTimeRaw;

            try {
                $aTs = is_string($aTime) ? Carbon::parse($aTime)->timestamp : 0;
            } catch (\Throwable $e) {
                $aTs = 0;
            }
            try {
                $bTs = is_string($bTime) ? Carbon::parse($bTime)->timestamp : 0;
            } catch (\Throwable $e) {
                $bTs = 0;
            }

            return $bTs <=> $aTs;
        });

        return $posts;
    }

    /**
     * Test page insights - displays formatted data and raw API response.
     * Route: GET panel/analytics/test?page_id={id}
     */
    public function testPageInsights(Request $request)
    {
        $user = User::find(auth()->id());
        $accounts = $user->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();

        [$since, $until] = $this->resolveDateRange($request);
        $pageId = $request->query('page_id');

        $selectedPage = null;
        $pageInsights = null;
        $pagePosts = null;
        $apiResponse = null;
        $pagePostsFetchResponse = null;

        if ($pageId && $facebookPages->contains('id', (int) $pageId)) {
            $selectedPage = Page::find($pageId);
            if ($selectedPage) {
                $pageInsights = $this->fetchPageInsights($selectedPage, $since, $until);
                $pagePostsFetchResponse = $this->fetchPagePostsForTest($selectedPage, $since, $until);
                $pagePosts = $pagePostsFetchResponse['posts'] ?? null;
                $apiResponse = [
                    'success' => true,
                    'pageInsights' => $pageInsights,
                    'pagePosts' => $pagePosts,
                    'pagePostsFetchResponse' => $pagePostsFetchResponse,
                    'selectedPage' => $selectedPage ? ['id' => $selectedPage->id, 'name' => $selectedPage->name] : null,
                    'since' => $since,
                    'until' => $until,
                ];
            }
        } elseif ($pageId) {
            $pagePostsFetchResponse = [
                'success' => false,
                'source' => 'validation',
                'posts' => null,
                'error' => 'Page not found or not owned by user.',
            ];
            $apiResponse = [
                'success' => false,
                'error' => 'Page not found or not owned by user.',
                'pagePostsFetchResponse' => $pagePostsFetchResponse,
            ];
        }

        return view('user.analytics.test', compact('facebookPages', 'pageId', 'selectedPage', 'pageInsights', 'pagePosts', 'apiResponse', 'since', 'until'));
    }

    /**
     * Test route: fetch posts and insights for a page for last_7 days and display all data.
     * Route: GET panel/test/page-insights/{page_id}
     */
    public function testPagePostsInsights(int $page_id)
    {
        $user = User::find(auth()->id());
        $accounts = $user->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();

        if (!$facebookPages->contains('id', $page_id)) {
            abort(404, 'Page not found or you do not have access.');
        }

        $page = Page::find($page_id);
        if (!$page || empty($page->page_id) || empty($page->access_token)) {
            abort(404, 'Page not found or missing Facebook credentials.');
        }

        $tokenCheck = FacebookService::validateToken($page);
        if (!$tokenCheck['success']) {
            return view('user.analytics.test-page-insights', [
                'page' => $page,
                'since' => null,
                'until' => null,
                'pageInsights' => null,
                'posts' => [],
                'error' => 'Invalid or expired access token.',
            ]);
        }

        $today = Carbon::today();
        $since = $today->copy()->subDays(7)->format('Y-m-d');
        $until = $today->format('Y-m-d');
        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;

        $pageInsights = $this->facebookService->getPageInsightsWithComparison(
            $page->page_id,
            $accessToken,
            $since,
            $until
        );

        $posts = $this->facebookService->getPagePostsWithInsights(
            $page->page_id,
            $accessToken,
            $since,
            $until
        );

        return view('user.analytics.test-page-insights', [
            'page' => $page,
            'since' => $since,
            'until' => $until,
            'pageInsights' => $pageInsights,
            'posts' => $posts,
            'error' => null,
        ]);
    }

}
