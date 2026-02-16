<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\ShortLink;
use App\Models\Feature;
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
        $user = Auth::guard('user')->user();
        $shortLinks = ShortLink::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('user.link-shortener.index', compact('shortLinks'));
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
