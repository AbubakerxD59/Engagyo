<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Page;
use App\Models\Board;
use App\Models\InstagramAccount;
use App\Models\Linkedin;
use App\Models\Tiktok;
use App\Models\Thread;
use App\Models\ShortLink;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\FeatureUsageService;
use App\Services\UrlShortenerService;

class LinkShortenerController extends Controller
{
    public function __construct(
        protected FeatureUsageService $featureUsageService,
        protected UrlShortenerService $urlShortenerService
    ) {}

    /**
     * Display the link shortener dashboard.
     */
    public function index()
    {
        $user = User::with('boards.pinterest', 'pages.facebook', 'tiktok', 'threads', 'instagramAccounts', 'linkedins')->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $shortLinks = ShortLink::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        // Multi-select value: user's saved url_shorten_platforms (array), used for link shortener dropdown
        $urlShortenPlatforms = $user->url_shorten_platforms ?? [];

        return view('user.link-shortener.index', compact('shortLinks', 'accounts', 'urlShortenPlatforms'));
    }

    /**
     * Update URL shortener status by platform. Selected platforms have all their accounts enabled.
     */
    public function platformUrlShortenerStatus(Request $request)
    {
        $request->validate([
            'platforms' => 'nullable|array',
            'platforms.*' => 'in:facebook,pinterest,tiktok,threads,instagram,linkedin',
        ]);

        $platforms = $request->platforms ?? [];
        $userId = Auth::guard('user')->id();
        $updated = 0;

        // Save selected platforms to user for link shortener multi-select and for new-account default
        $user = User::find($userId);
        if ($user) {
            $user->url_shorten_platforms = $platforms;
            $user->save();
        }

        // Facebook pages
        $count = Page::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('facebook', $platforms)]);
        $updated += $count;

        // Pinterest boards
        $count = Board::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('pinterest', $platforms)]);
        $updated += $count;

        // TikTok accounts
        $count = Tiktok::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('tiktok', $platforms)]);
        $updated += $count;

        // Threads accounts
        $count = Thread::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('threads', $platforms)]);
        $updated += $count;

        // Instagram accounts
        $count = InstagramAccount::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('instagram', $platforms)]);
        $updated += $count;

        // LinkedIn accounts
        $count = Linkedin::where('user_id', $userId)->update(['url_shortener_enabled' => in_array('linkedin', $platforms)]);
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

        if ($type === 'threads') {
            $thread = Thread::where('id', $id)->where('user_id', Auth::guard('user')->id())->first();
            if ($thread) {
                $thread->url_shortener_enabled = (bool) $status;
                $thread->save();
                return response()->json(['success' => true, 'message' => 'Status changed successfully!']);
            }
        }

        if ($type === 'instagram') {
            $instagram = InstagramAccount::where('id', $id)->where('user_id', Auth::guard('user')->id())->first();
            if ($instagram) {
                $instagram->url_shortener_enabled = (bool) $status;
                $instagram->save();
                return response()->json(['success' => true, 'message' => 'Status changed successfully!']);
            }
        }

        if ($type === 'linkedin') {
            $linkedin = Linkedin::where('id', $id)->where('user_id', Auth::guard('user')->id())->first();
            if ($linkedin) {
                $linkedin->url_shortener_enabled = (bool) $status;
                $linkedin->save();
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
            'accounts.*.type' => 'required|in:facebook,pinterest,tiktok,threads,instagram,linkedin',
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
            } elseif ($type === 'threads') {
                $thread = Thread::where('id', $id)->where('user_id', $userId)->first();
                if ($thread) {
                    $thread->url_shortener_enabled = $status;
                    $thread->save();
                    $updated++;
                }
            } elseif ($type === 'instagram') {
                $instagram = InstagramAccount::where('id', $id)->where('user_id', $userId)->first();
                if ($instagram) {
                    $instagram->url_shortener_enabled = $status;
                    $instagram->save();
                    $updated++;
                }
            } elseif ($type === 'linkedin') {
                $linkedin = Linkedin::where('id', $id)->where('user_id', $userId)->first();
                if ($linkedin) {
                    $linkedin->url_shortener_enabled = $status;
                    $linkedin->save();
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
                    'data' => $this->shortLinkPayload($existing, true),
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

            $created = $this->urlShortenerService->createShortLink(
                $user->id,
                $request->original_url,
                $request->userAgent(),
                $request->ip()
            );

            if (! $created['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $created['message'] ?? 'Failed to create short link.',
                ], 422);
            }

            $shortLink = ShortLink::find($created['id'] ?? 0);

            return response()->json([
                'success' => true,
                'message' => ($created['existing'] ?? false)
                    ? 'This URL was already shortened. Here is your existing short link.'
                    : 'Short link created successfully!',
                'data' => $shortLink
                    ? $this->shortLinkPayload($shortLink, (bool) ($created['existing'] ?? false))
                    : [
                        'short_code' => $created['short_code'] ?? '',
                        'original_url' => $created['original_url'] ?? $normalizedUrl,
                        'short_url' => $created['short_url'] ?? '',
                        'clicks' => $created['clicks'] ?? 0,
                        'created_at' => $created['created_at'] ?? now()->toIso8601String(),
                        'existing' => (bool) ($created['existing'] ?? false),
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
                'data' => $this->shortLinkPayload($shortLink->fresh(), false),
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

    /**
     * @return array<string, mixed>
     */
    protected function shortLinkPayload(ShortLink $shortLink, bool $existing): array
    {
        return [
            'id' => $shortLink->id,
            'short_code' => $shortLink->short_code,
            'original_url' => $shortLink->original_url,
            'short_url' => $shortLink->publicShortUrl(),
            'clicks' => $shortLink->clicks,
            'created_at' => $shortLink->created_at->toIso8601String(),
            'existing' => $existing,
        ];
    }
}
