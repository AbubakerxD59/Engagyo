<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageInsight;
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
     * Optional ?page_id=X filters by that Facebook page.
     * When a page is selected, fetches page-level insights (followers, reach, etc.).
     */
    public function index(Request $request)
    {
        $accounts = auth()->user()->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();

        [$since, $until] = $this->resolveDateRange($request);

        $pageId = $request->query('page_id');
        $selectedPage = null;
        $pageInsights = null;
        $refresh = (bool) $request->query('refresh', false);

        if ($pageId && $facebookPages->contains('id', (int) $pageId)) {
            $selectedPage = Page::find($pageId);
            if ($selectedPage) {
                $pageInsights = $this->fetchPageInsights($selectedPage, $since, $until, $refresh);
            }
        }

        $duration = $request->query('duration', 'last_28');
        return view('user.analytics.index', compact('facebookPages', 'pageId', 'pageInsights', 'selectedPage', 'since', 'until', 'duration'));
    }

    /**
     * Return page insights data as JSON for AJAX.
     */
    public function data(Request $request)
    {
        $accounts = auth()->user()->getAccounts();
        $facebookPages = $accounts->where('type', 'facebook')->values();

        [$since, $until] = $this->resolveDateRange($request);
        $refresh = (bool) $request->query('refresh', false);

        $pageId = $request->query('page_id');
        $selectedPage = null;
        $pageInsights = null;

        if ($pageId && $facebookPages->contains('id', (int) $pageId)) {
            $selectedPage = Page::find($pageId);
            if ($selectedPage) {
                $pageInsights = $this->fetchPageInsights($selectedPage, $since, $until, $refresh);
            }
        }

        return response()->json([
            'success' => true,
            'pageInsights' => $pageInsights,
            'selectedPage' => $selectedPage ? ['id' => $selectedPage->id, 'name' => $selectedPage->name] : null,
            'hasPages' => $facebookPages->count() > 0,
            'since' => $since,
            'until' => $until,
        ]);
    }

    /**
     * Fetch page-level insights (followers, reach, video views, engagements, link clicks, CTR).
     * Returns from DB if stored; fetches from Graph API only when refresh or no stored data.
     */
    private function fetchPageInsights(?Page $page, ?string $since = null, ?string $until = null, bool $refresh = false): ?array
    {
        if (!$page || empty($page->page_id) || empty($page->access_token)) {
            return null;
        }

        $duration = request()->query('duration', 'last_28');

        if (!$refresh) {
            $stored = PageInsight::where('page_id', $page->id)
                ->where('since', $since)
                ->where('until', $until)
                ->first();

            if ($stored && $stored->insights) {
                return $stored->insights;
            }
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

}
