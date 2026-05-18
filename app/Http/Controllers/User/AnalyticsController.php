<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardInsight;
use App\Models\FacebookPost;
use App\Models\InstagramAccount;
use App\Models\InstagramInsight;
use App\Models\InstagramPost;
use App\Models\Page;
use App\Models\PageInsight;
use App\Models\PinterestPin;
use App\Models\Thread;
use App\Models\ThreadInsight;
use App\Models\ThreadPost;
use App\Models\Tiktok;
use App\Models\TiktokInsight;
use App\Models\TiktokPost;
use App\Models\User;
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
    public function __construct() {}

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
        $pinterestBoards = $accounts->where('type', 'pinterest')->values();
        $tiktokAccounts = $accounts->where('type', 'tiktok')->values();
        $instagramAccounts = $accounts->where('type', 'instagram')->values();
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
        )->concat(
            $pinterestBoards->map(function ($board) {
                $p = $board->pinterest;

                return [
                    'ref' => 'pinterest-board:'.$board->id,
                    'platform' => 'pinterest',
                    'id' => $board->id,
                    'name' => $board->name,
                    'username' => $p ? ('@'.$p->username) : 'Pinterest',
                    'profile_image' => $p ? ($p->profile_image ?? social_logo('pinterest')) : social_logo('pinterest'),
                ];
            })->values()
        )->concat(
            $tiktokAccounts->map(function ($tiktok) {
                return [
                    'ref' => 'tiktok:'.$tiktok->id,
                    'platform' => 'tiktok',
                    'id' => $tiktok->id,
                    'name' => $tiktok->display_name ?? $tiktok->username,
                    'username' => $tiktok->username ? '@'.$tiktok->username : 'TikTok',
                    'profile_image' => $tiktok->profile_image ?? social_logo('tiktok'),
                ];
            })->values()
        )->concat(
            $instagramAccounts->map(function ($ig) {
                return [
                    'ref' => 'instagram:'.$ig->id,
                    'platform' => 'instagram',
                    'id' => $ig->id,
                    'name' => $ig->name ?? $ig->username,
                    'username' => $ig->username ? '@'.$ig->username : 'Instagram',
                    'profile_image' => $ig->profile_image ?? social_logo('instagram'),
                ];
            })->values()
        )->values();
        $userTimezoneName = $user->timezone && !empty($user->timezone->name) ? $user->timezone->name : 'UTC';

        $today = Carbon::today();
        $since = $today->copy()->subDays(28)->format('Y-m-d');
        $until = $today->format('Y-m-d');
        $duration = 'last_28';

        $selectedPage = null;
        return view('user.analytics.index', compact('facebookPages', 'threadsAccounts', 'pinterestBoards', 'analyticsAccounts', 'selectedPage', 'since', 'until', 'duration', 'userTimezoneName'));
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
        $pinterestBoards = $accounts->where('type', 'pinterest')->values();
        $tiktokAccounts = $accounts->where('type', 'tiktok')->values();
        $instagramAccounts = $accounts->where('type', 'instagram')->values();

        [$since, $until] = $this->resolveDateRange($request);

        $accountRef = (string) $request->query('account_ref', '');
        $pageId = $request->query('page_id');
        $selectedPage = null;
        $pageInsights = null;
        $pagePosts = null;
        $postsFetching = false;
        $postsFetchingMessage = null;
        $pagePostsTotal = 0;
        $pagePostsHasMore = false;
        $pagePostsNextOffset = 0;
        $platform = 'facebook';
        $postsOffset = max(0, (int) $request->query('posts_offset', 0));
        $postsLimitInput = $request->query('posts_limit');
        $postsLimit = is_null($postsLimitInput) ? null : max(1, min(50, (int) $postsLimitInput));

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
                    if (is_array($pagePosts) && empty($pagePosts)) {
                        $postsFetching = true;
                        $postsFetchingMessage = 'Posts for this page are being fetched. Please check back shortly.';
                    }
                    if (is_array($pagePosts)) {
                        $pagePostsTotal = count($pagePosts);
                        if ($postsLimit !== null) {
                            $pagePosts = array_slice($pagePosts, $postsOffset, $postsLimit);
                            $pagePostsNextOffset = $postsOffset + count($pagePosts);
                            $pagePostsHasMore = $pagePostsNextOffset < $pagePostsTotal;
                        } else {
                            $pagePostsNextOffset = $pagePostsTotal;
                        }
                    }
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
                    if (is_array($pagePosts)) {
                        $pagePostsTotal = count($pagePosts);
                        if ($postsLimit !== null) {
                            $pagePosts = array_slice($pagePosts, $postsOffset, $postsLimit);
                            $pagePostsNextOffset = $postsOffset + count($pagePosts);
                            $pagePostsHasMore = $pagePostsNextOffset < $pagePostsTotal;
                        } else {
                            $pagePostsNextOffset = $pagePostsTotal;
                        }
                    }
                    $selectedPage = ['id' => $selected->id, 'name' => $selected->username];
                }
            }
        } elseif (str_starts_with($accountRef, 'pinterest-board:')) {
            $platform = 'pinterest';
            $id = (int) str_replace('pinterest-board:', '', $accountRef);
            if ($pinterestBoards->contains('id', $id)) {
                $selected = Board::with('pinterest')->find($id);
                if ($selected) {
                    $pageInsights = $this->fetchPinterestBoardInsights($selected, $since, $until);
                    $pagePosts = $this->fetchPinterestBoardPosts($selected, $since, $until);
                    if (is_array($pagePosts)) {
                        $pagePostsTotal = count($pagePosts);
                        if ($postsLimit !== null) {
                            $pagePosts = array_slice($pagePosts, $postsOffset, $postsLimit);
                            $pagePostsNextOffset = $postsOffset + count($pagePosts);
                            $pagePostsHasMore = $pagePostsNextOffset < $pagePostsTotal;
                        } else {
                            $pagePostsNextOffset = $pagePostsTotal;
                        }
                    }
                    $pinterest = $selected->pinterest;
                    $selectedPage = [
                        'id' => $selected->id,
                        'name' => $selected->name.($pinterest ? ' · @'.$pinterest->username : ''),
                    ];
                }
            }
        } elseif (str_starts_with($accountRef, 'tiktok:')) {
            $platform = 'tiktok';
            $id = (int) str_replace('tiktok:', '', $accountRef);
            if ($tiktokAccounts->contains('id', $id)) {
                $selected = Tiktok::find($id);
                if ($selected) {
                    $pageInsights = $this->fetchTiktokInsights($selected, $since, $until);
                    $pagePosts = $this->fetchTiktokPosts($selected, $since, $until);
                    $hasStoredTiktokPosts = TiktokPost::where('tiktok_id', $selected->id)->exists();
                    if (is_array($pagePosts) && empty($pagePosts) && ! $hasStoredTiktokPosts) {
                        $postsFetching = true;
                        $postsFetchingMessage = 'TikTok videos for this account are being fetched. Please check back shortly.';
                    }
                    if (is_array($pagePosts)) {
                        $pagePostsTotal = count($pagePosts);
                        if ($postsLimit !== null) {
                            $pagePosts = array_slice($pagePosts, $postsOffset, $postsLimit);
                            $pagePostsNextOffset = $postsOffset + count($pagePosts);
                            $pagePostsHasMore = $pagePostsNextOffset < $pagePostsTotal;
                        } else {
                            $pagePostsNextOffset = $pagePostsTotal;
                        }
                    }
                    $selectedPage = [
                        'id' => $selected->id,
                        'name' => $selected->display_name ?? $selected->username,
                    ];
                }
            }
        } elseif (str_starts_with($accountRef, 'instagram:')) {
            $platform = 'instagram';
            $id = (int) str_replace('instagram:', '', $accountRef);
            if ($instagramAccounts->contains('id', $id)) {
                $selected = InstagramAccount::find($id);
                if ($selected) {
                    $pageInsights = $this->fetchInstagramInsights($selected, $since, $until);
                    $pagePosts = $this->fetchInstagramPosts($selected, $since, $until);
                    $hasStoredInstagramPosts = InstagramPost::where('instagram_account_id', $selected->id)->exists();
                    if (is_array($pagePosts) && empty($pagePosts) && ! $hasStoredInstagramPosts) {
                        $postsFetching = true;
                        $postsFetchingMessage = 'Instagram posts for this account are being fetched. Please check back shortly.';
                    }
                    if (is_array($pagePosts)) {
                        $pagePostsTotal = count($pagePosts);
                        if ($postsLimit !== null) {
                            $pagePosts = array_slice($pagePosts, $postsOffset, $postsLimit);
                            $pagePostsNextOffset = $postsOffset + count($pagePosts);
                            $pagePostsHasMore = $pagePostsNextOffset < $pagePostsTotal;
                        } else {
                            $pagePostsNextOffset = $pagePostsTotal;
                        }
                    }
                    $selectedPage = [
                        'id' => $selected->id,
                        'name' => $selected->name ?? $selected->username,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'pageInsights' => $pageInsights,
            'pagePosts' => $pagePosts,
            'posts_fetching' => $postsFetching,
            'posts_fetching_message' => $postsFetchingMessage,
            'pagePostsTotal' => $pagePostsTotal,
            'pagePostsHasMore' => $pagePostsHasMore,
            'pagePostsNextOffset' => $pagePostsNextOffset,
            'selectedPage' => $selectedPage,
            'hasPages' => ($facebookPages->count() + $threadsAccounts->count() + $pinterestBoards->count() + $tiktokAccounts->count() + $instagramAccounts->count()) > 0,
            'platform' => $platform,
            'accountRef' => $accountRef,
            'since' => $since,
            'until' => $until,
        ]);
    }

    /**
     * Fetch page-level insights (followers, reach, video views, engagements) from DB only.
     */
    private function fetchPageInsights(?Page $page, ?string $since = null, ?string $until = null): ?array
    {
        if (!$page || empty($page->page_id) || empty($page->access_token)) {
            return null;
        }

        $stored = PageInsight::where('page_id', $page->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();

        if ($stored && $stored->insights) {
            return $stored->insights;
        }

        return null;
    }

    /**
     * Fetch page posts with insights from cache/DB only.
     * Returns null for "All Accounts" (posts not aggregated across pages).
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

        $stored = FacebookPost::query()
            ->where('fb_page_id', $page->page_id)
            ->whereBetween('post_created_date', [$since.' 00:00:00', $until.' 23:59:59'])
            ->orderByDesc('post_created_date')
            ->get();

        if ($stored->isNotEmpty()) {
            $storedPosts = $stored->map(function (FacebookPost $row) {
                $post = is_array($row->post_data) ? $row->post_data : [];
                $insights = is_array($row->post_insights) ? $row->post_insights : [];

                if (! isset($post['insights']) || ! is_array($post['insights'])) {
                    $post['insights'] = $insights;
                }

                $post['post_id'] = $post['post_id'] ?? $row->fb_post_id;
                $post['id'] = $post['id'] ?? $row->fb_post_id;
                $post['created_time'] = $post['created_time'] ?? $row->post_created_date;
                $post['permalink_url'] = $post['permalink_url'] ?? $row->permalink_url;
                $post['status_type'] = $post['status_type'] ?? $row->status_type;
                $post['type'] = $post['type'] ?? $row->post_type;
                $post['shares'] = $post['shares'] ?? $row->shares_count;
                $post['comments'] = $post['comments'] ?? $row->comments_count;

                return $post;
            })->values()->all();

            Cache::put($cacheKey, $storedPosts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));

            return $storedPosts;
        }

        return [];
    }

    /**
     * Fetch Threads account insights (cron-synced `thread_insights` rows only).
     */
    private function fetchThreadInsights(?Thread $thread, ?string $since = null, ?string $until = null): ?array
    {
        if (! $thread || empty($thread->threads_id) || empty($thread->access_token)) {
            return null;
        }

        $stored = ThreadInsight::where('thread_id', $thread->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();
        if ($stored && $stored->insights) {
            return $stored->insights;
        }

        return null;
    }

    /**
     * Fetch Threads posts with insights (`thread_posts` / cache only; no live API from this controller).
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

        $stored = ThreadPost::forCreatedDateRange((int) $thread->id, $since, $until);
        if ($stored->isNotEmpty()) {
            $storedPosts = $stored->map(fn (ThreadPost $row) => $row->toAnalyticsPostArray())->values()->all();
            Cache::put($cacheKey, $storedPosts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));

            return $storedPosts;
        }

        return [];
    }

    /**
     * TikTok account insights (`tiktok_insights` only; sync runs on schedule/cron).
     */
    private function fetchTiktokInsights(?Tiktok $account, ?string $since = null, ?string $until = null): ?array
    {
        if (! $account || empty($account->access_token)) {
            return null;
        }

        $stored = TiktokInsight::where('tiktok_id', $account->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();

        if ($stored && $stored->insights) {
            return $stored->insights;
        }

        return null;
    }

    /**
     * TikTok videos with metrics (`tiktok_posts` / cache only).
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchTiktokPosts(?Tiktok $account, ?string $since = null, ?string $until = null): ?array
    {
        if (! $account || empty($account->access_token)) {
            return null;
        }

        $since = $since ?: Carbon::today()->subDays(28)->format('Y-m-d');
        $until = $until ?: Carbon::today()->format('Y-m-d');

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));
        $cacheKey = $this->analyticsPostsCacheKey((int) auth()->id(), (int) $account->id, $duration, $since, $until, 'tiktok');

        $cachedPosts = Cache::get($cacheKey);
        if (is_array($cachedPosts) && count($cachedPosts) > 0) {
            return $cachedPosts;
        }

        $stored = TiktokPost::forCreatedDateRange((int) $account->id, $since, $until);
        $storedPosts = $stored->map(fn (TiktokPost $row) => $row->toAnalyticsPostArray())->values()->all();

        Cache::put($cacheKey, $storedPosts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));

        return $storedPosts;
    }

    /**
     * Instagram account insights (`instagram_insights` only; sync runs on schedule/cron).
     */
    private function fetchInstagramInsights(?InstagramAccount $account, ?string $since = null, ?string $until = null): ?array
    {
        if (! $account || empty($account->ig_user_id) || empty($account->access_token)) {
            return null;
        }

        $stored = InstagramInsight::where('instagram_account_id', $account->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();

        if ($stored && $stored->insights) {
            return $stored->insights;
        }

        return null;
    }

    /**
     * Instagram media with insights (`instagram_posts` / cache only).
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchInstagramPosts(?InstagramAccount $account, ?string $since = null, ?string $until = null): ?array
    {
        if (! $account || empty($account->ig_user_id) || empty($account->access_token)) {
            return null;
        }

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));
        $cacheKey = $this->analyticsPostsCacheKey((int) auth()->id(), (int) $account->id, $duration, $since, $until, 'instagram');

        $cachedPosts = Cache::get($cacheKey);
        if ($cachedPosts !== null) {
            return $cachedPosts;
        }

        $stored = InstagramPost::forCreatedDateRange((int) $account->id, $since ?? '', $until ?? '');
        if ($stored->isNotEmpty()) {
            $storedPosts = $stored->map(fn (InstagramPost $row) => $row->toAnalyticsPostArray())->values()->all();
            Cache::put($cacheKey, $storedPosts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));

            return $storedPosts;
        }

        return [];
    }

    /**
     * Pinterest board-level insights (`board_insights` only; sync runs on schedule/cron).
     */
    private function fetchPinterestBoardInsights(?Board $board, ?string $since = null, ?string $until = null): ?array
    {
        if (! $board || empty($board->board_id)) {
            return null;
        }

        $stored = BoardInsight::where('board_id', $board->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();
        if ($stored && $stored->insights) {
            return $stored->insights;
        }

        return null;
    }

    /**
     * Pinterest pins for the board (DB / cache only; sync runs on schedule/cron).
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchPinterestBoardPosts(?Board $board, ?string $since = null, ?string $until = null): ?array
    {
        if (! $board || empty($board->board_id)) {
            return null;
        }

        $duration = $this->normalizeDuration(request()->query('duration', 'last_28'));
        $cacheKey = $this->analyticsPostsCacheKey((int) auth()->id(), (int) $board->id, $duration, $since, $until, 'pinterest-board');

        $cachedPosts = Cache::get($cacheKey);
        if ($cachedPosts !== null) {
            return $cachedPosts;
        }

        $stored = PinterestPin::latestForBoard((int) $board->id);
        if ($stored->isNotEmpty()) {
            $storedPosts = $stored->map(fn (PinterestPin $row) => $row->toAnalyticsPostArray())->values()->all();
            Cache::put($cacheKey, $storedPosts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));

            return $storedPosts;
        }

        return [];
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
     * Fetch page posts for analytics test view (database / app cache only; no live Graph API).
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
        if ($since !== null && $until !== null) {
            request()->merge([
                'since' => $since,
                'until' => $until,
                'duration' => $duration,
            ]);
        }

        $posts = $this->fetchPagePosts($page, $since, $until);

        return [
            'success' => true,
            'source' => 'database',
            'posts' => is_array($posts) ? $posts : [],
            'error' => null,
        ];
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
     * Test route: show posts and insights from DB/cache only (last 7 days). No live Graph API.
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

        $today = Carbon::today();
        $since = $today->copy()->subDays(7)->format('Y-m-d');
        $until = $today->format('Y-m-d');

        request()->merge([
            'since' => $since,
            'until' => $until,
            'duration' => 'last_7',
        ]);

        $pageInsights = $this->fetchPageInsights($page, $since, $until);
        $posts = $this->fetchPagePosts($page, $since, $until);

        return view('user.analytics.test-page-insights', [
            'page' => $page,
            'since' => $since,
            'until' => $until,
            'pageInsights' => $pageInsights,
            'posts' => is_array($posts) ? $posts : [],
            'error' => null,
        ]);
    }

}
