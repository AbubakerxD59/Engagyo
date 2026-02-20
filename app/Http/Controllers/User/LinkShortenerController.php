<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Page;
use App\Models\Board;
use App\Models\Tiktok;
use App\Models\ShortLink;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\FeatureUsageService;

class LinkShortenerController extends Controller
{
    protected $featureUsageService;

    public function __construct(FeatureUsageService $featureUsageService)
    {
        $this->featureUsageService = $featureUsageService;
    }

    /**
     * Display the link shortener dashboard.
     */
    public function index()
    {
        $user = User::with('boards.pinterest', 'pages.facebook', 'tiktok')->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $shortLinks = ShortLink::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        // Platforms enabled for URL shortener: platform is selected if ALL its accounts have url_shortener_enabled
        $enabledPlatforms = [];
        if ($user->pages->isNotEmpty() && $user->pages->every(fn($p) => (bool) ($p->url_shortener_enabled ?? false))) {
            $enabledPlatforms[] = 'facebook';
        }
        if ($user->boards->isNotEmpty() && $user->boards->every(fn($b) => (bool) ($b->url_shortener_enabled ?? false))) {
            $enabledPlatforms[] = 'pinterest';
        }
        if ($user->tiktok->isNotEmpty() && $user->tiktok->every(fn($t) => (bool) ($t->url_shortener_enabled ?? false))) {
            $enabledPlatforms[] = 'tiktok';
        }

        return view('user.link-shortener.index', compact('shortLinks', 'accounts', 'enabledPlatforms'));
    }

    /**
     * Update URL shortener status by platform. Selected platforms have all their accounts enabled.
     */
    public function platformUrlShortenerStatus(Request $request)
    {
        $request->validate([
            'platforms' => 'required|array',
            'platforms.*' => 'in:facebook,pinterest,tiktok',
        ]);

        $platforms = $request->platforms;
        $userId = Auth::guard('user')->id();
        $updated = 0;

        // Facebook pages
        $count = Page::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('facebook', $platforms)]);
        $updated += $count;

        // Pinterest boards
        $count = Board::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('pinterest', $platforms)]);
        $updated += $count;

        // TikTok accounts
        $count = Tiktok::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('tiktok', $platforms)]);
        $updated += $count;

        return response()->json([
            'success' => true,
            'message' => 'URL shortener settings updated for selected platforms.',
            'updated' => $updated,
        ]);
    }

    /**
     * Toggle URL shortener enabled status for an account.
     */
    public function accountUrlShortenerStatus(Request $request)
    {
        $type = $request->type;
        $id = $request->id;
        $status = $request->status; // 1 = enabled, 0 = disabled

        if ($type === 'facebook') {
            $page = Page::where('id', $id)->where('user_id', Auth::guard('user')->id())->first();
            if ($page) {
                $page->url_shortener_enabled = (bool) $status;
                $page->save();
                return response()->json(['success' => true, 'message' => 'Status changed successfully!']);
            }
        }

        if ($type === 'pinterest') {
            $board = Board::where('id', $id)->where('user_id', Auth::guard('user')->id())->first();
            if ($board) {
                $board->url_shortener_enabled = (bool) $status;
                $board->save();
                return response()->json(['success' => true, 'message' => 'Status changed successfully!']);
            }
        }

        if ($type === 'tiktok') {
            $tiktok = Tiktok::where('id', $id)->where('user_id', Auth::guard('user')->id())->first();
            if ($tiktok) {
                $tiktok->url_shortener_enabled = (bool) $status;
                $tiktok->save();
                return response()->json(['success' => true, 'message' => 'Status changed successfully!']);
            }
        }

        return response()->json(['success' => false, 'message' => 'Account not found.']);
    }

    /**
     * Bulk update URL shortener status for multiple accounts in a single request.
     * Expects: accounts = [{type: 'facebook', id: 1}, {type: 'pinterest', id: 2}, ...], status = 0 or 1
     */
    public function accountUrlShortenerStatusBulk(Request $request)
    {
        $request->validate([
            'accounts' => 'required|array',
            'accounts.*.type' => 'required|in:facebook,pinterest,tiktok',
            'accounts.*.id' => 'required|integer',
            'status' => 'required|in:0,1',
        ]);

        $accounts = $request->accounts;
        $status = (bool) $request->status;
        $userId = Auth::guard('user')->id();
        $updated = 0;

        foreach ($accounts as $item) {
            $type = $item['type'];
            $id = (int) $item['id'];

            if ($type === 'facebook') {
                $page = Page::where('id', $id)->where('user_id', $userId)->first();
                if ($page) {
                    $page->url_shortener_enabled = $status;
                    $page->save();
                    $updated++;
                }
            } elseif ($type === 'pinterest') {
                $board = Board::where('id', $id)->where('user_id', $userId)->first();
                if ($board) {
                    $board->url_shortener_enabled = $status;
                    $board->save();
                    $updated++;
                }
            } elseif ($type === 'tiktok') {
                $tiktok = Tiktok::where('id', $id)->where('user_id', $userId)->first();
                if ($tiktok) {
                    $tiktok->url_shortener_enabled = $status;
                    $tiktok->save();
                    $updated++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $status
                ? 'URL shortener enabled for all selected accounts.'
                : 'URL shortener disabled for all selected accounts.',
            'updated' => $updated,
        ]);
    }

    /**
     * Store a new short link. If this user already shortened this URL, returns the existing short link.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();

            $request->validate([
                'original_url' => 'required|url|max:2048',
            ]);

            $normalizedUrl = ShortLink::normalizeUrl($request->original_url);

            $existing = ShortLink::where('user_id', $user->id)
                ->where('original_url', $normalizedUrl)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'This URL was already shortened. Here is your existing short link.',
                    'data' => [
                        'id' => $existing->id,
                        'short_code' => $existing->short_code,
                        'original_url' => $existing->original_url,
                        'short_url' => url('/s/' . $existing->short_code),
                        'clicks' => $existing->clicks,
                        'created_at' => $existing->created_at->toIso8601String(),
                        'existing' => true,
                    ],
                ]);
            }

            $result = $this->featureUsageService->checkAndIncrement($user, Feature::$features_list[7], 1);

            if (!$result['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'usage' => $result['usage'] ?? 0,
                    'limit' => $result['limit'] ?? null,
                    'remaining' => $result['remaining'] ?? null,
                ], 403);
            }

            $shortCode = ShortLink::generateUniqueCode(6);

            $shortLink = ShortLink::create([
                'user_id' => $user->id,
                'short_code' => $shortCode,
                'original_url' => $normalizedUrl,
            ]);

            $shortUrl = url('/s/' . $shortCode);

            return response()->json([
                'success' => true,
                'message' => 'Short link created successfully!',
                'data' => [
                    'id' => $shortLink->id,
                    'short_code' => $shortLink->short_code,
                    'original_url' => $shortLink->original_url,
                    'short_url' => $shortUrl,
                    'clicks' => 0,
                    'created_at' => $shortLink->created_at->toIso8601String(),
                    'existing' => false,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update an existing short link (original URL only).
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::guard('user')->user();

            $request->validate([
                'original_url' => 'required|url|max:2048',
            ]);

            $shortLink = ShortLink::where('user_id', $user->id)->where('id', $id)->firstOrFail();

            $shortLink->update([
                'original_url' => $request->original_url,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Short link updated successfully!',
                'data' => [
                    'id' => $shortLink->id,
                    'short_code' => $shortLink->short_code,
                    'original_url' => $shortLink->original_url,
                    'short_url' => url('/s/' . $shortLink->short_code),
                    'clicks' => $shortLink->clicks,
                    'created_at' => $shortLink->created_at->toIso8601String(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified short link.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::guard('user')->user();
            $shortLink = ShortLink::where('user_id', $user->id)->where('id', $id)->firstOrFail();
            $shortLink->delete();

            $user->decrementFeatureUsage(Feature::$features_list[7], 1);

            return response()->json([
                'success' => true,
                'message' => 'Short link deleted successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
