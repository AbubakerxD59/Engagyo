<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\PublishInstagramPost;
use App\Models\InstagramAccount;
use App\Models\Post;
use App\Services\FacebookService;
use App\Services\InstagramGraphService;
use App\Services\PostService;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dev-only: creates a real Instagram carousel post using Content Publishing API.
 * Enable with INSTAGRAM_CAROUSEL_TEST_ENABLED=true in .env
 *
 * GET .../panel/schedule/dev/instagram-carousel-test?account_id=1&sync=1
 *   &urls=https://...,https://...
 */
class InstagramCarouselTestController extends Controller
{
    public function __invoke(Request $request, InstagramGraphService $instagramGraphService): JsonResponse
    {
        abort_unless((bool) config('services.instagram.carousel_test_enabled'), 404);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $queryUrls = trim((string) $request->query('urls', ''));
        if ($queryUrls !== '') {
            $urls = array_values(array_filter(array_map('trim', preg_split('/[|,]/', $queryUrls) ?: [])));
        } else {
            $urls = [
                'https://picsum.photos/seed/engagyo-carousel-a/1080/1080.jpg',
                'https://picsum.photos/seed/engagyo-carousel-b/1080/1080.jpg',
            ];
        }

        $urls = array_values(array_filter($urls, fn ($u) => is_string($u) && $u !== ''));
        foreach ($urls as $u) {
            if (! filter_var($u, FILTER_VALIDATE_URL) || ! preg_match('#^https://#i', $u)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each url must be a valid https URL. Invalid: '.$u,
                ], 422);
            }
        }

        if (count($urls) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'At least two https image (or video) URLs are required. Pass ?urls=url1,url2',
            ], 422);
        }
        if (count($urls) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Instagram allows at most 10 carousel items.',
            ], 422);
        }

        $igQuery = InstagramAccount::query()->where('user_id', $user->id);
        if ($request->filled('account_id')) {
            $igQuery->where('id', $request->integer('account_id'));
        }
        $ig = $igQuery->first();
        if (! $ig) {
            return response()->json([
                'success' => false,
                'message' => 'No Instagram account found for this user. Connect Instagram or pass ?account_id= (instagram_accounts.id).',
            ], 422);
        }

        $tokenResponse = FacebookService::validateToken($ig);
        if (! $tokenResponse['success']) {
            return response()->json([
                'success' => false,
                'message' => $tokenResponse['message'] ?? 'Invalid or expired page token for Instagram.',
            ], 422);
        }
        $accessToken = $tokenResponse['access_token'];

        $carouselMedia = [];
        foreach ($urls as $u) {
            $isVideo = (bool) preg_match('#\.(mp4|mov|m4v)(\?|$)#i', $u);
            $carouselMedia[] = ['type' => $isVideo ? 'video' : 'photo', 'path' => $u];
        }

        $publishDate = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

        $post = PostService::create([
            'user_id' => $user->id,
            'account_id' => $ig->id,
            'social_type' => 'instagram',
            'type' => 'carousel',
            'source' => 'instagram_carousel_test',
            'title' => $request->query('caption', 'Engagyo carousel test'),
            'comment' => null,
            'image' => null,
            'video' => 0,
            'metadata' => json_encode(['carousel_media' => $carouselMedia]),
            'publish_date' => $publishDate,
        ]);

        $preview = PostService::postTypeBody($post);

        $sync = $request->boolean('sync');
        if ($sync) {
            try {
                set_time_limit(1300);
                $instagramGraphService->publishPost($post->fresh(['instagramAccount']), $accessToken);
            } catch (\Throwable $e) {
                $post->refresh();

                return response()->json([
                    'success' => false,
                    'mode' => 'sync',
                    'post_id' => $post->id,
                    'message' => $e->getMessage(),
                    'post' => [
                        'status' => $post->status,
                        'response' => $post->response,
                    ],
                ], 500);
            }

            $post->refresh();

            return response()->json([
                'success' => (int) $post->status === 1,
                'mode' => 'sync',
                'post_id' => $post->id,
                'instagram_media_id' => $post->post_id,
                'resolved_carousel_items' => $preview['carousel_items'] ?? [],
                'post' => [
                    'status' => $post->status,
                    'response' => $post->response,
                ],
            ]);
        }

        PublishInstagramPost::dispatch($post->id, $accessToken);

        return response()->json([
            'success' => true,
            'mode' => 'async',
            'post_id' => $post->id,
            'message' => 'Job queued. Poll post status in schedule or DB. Use ?sync=1 to publish in-request (long).',
            'resolved_carousel_items' => $preview['carousel_items'] ?? [],
        ]);
    }
}
