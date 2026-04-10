<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\PublishInstagramPost;
use App\Models\InstagramAccount;
use App\Services\FacebookService;
use App\Services\InstagramGraphService;
use App\Services\PostService;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * Dev-only: UI + API for Instagram carousel Content Publishing tests.
 * Enable with INSTAGRAM_CAROUSEL_TEST_ENABLED=true
 */
class InstagramCarouselTestController extends Controller
{
    public function show(Request $request): View|JsonResponse
    {
        abort_unless((bool) config('services.instagram.carousel_test_enabled'), 404);

        $user = Auth::guard('user')->user();
        if (! $user) {
            abort(401);
        }

        if ($request->wantsJson() || $request->boolean('json')) {
            return $this->publishFromQuery($request);
        }

        $accounts = InstagramAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('username')
            ->get();

        return view('user.schedule.instagram-carousel-test', [
            'accounts' => $accounts,
        ]);
    }

    public function publish(Request $request, InstagramGraphService $instagramGraphService): JsonResponse
    {
        abort_unless((bool) config('services.instagram.carousel_test_enabled'), 404);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        set_time_limit(1300);

        $steps = [];
        $pushStep = function (array $step) use (&$steps): void {
            $steps[] = $step;
        };

        $ig = $this->resolveInstagramAccount($request, $user);
        if ($ig instanceof JsonResponse) {
            return $ig;
        }

        $pushStep([
            'key' => 'test.account',
            'message' => 'Using Instagram account: @'.($ig->username ?? $ig->ig_user_id).' (DB id '.$ig->id.').',
            'status' => 'ok',
            'at' => now()->toIso8601String(),
        ]);

        $tokenResponse = FacebookService::validateToken($ig);
        if (! $tokenResponse['success']) {
            $pushStep([
                'key' => 'test.token',
                'message' => $tokenResponse['message'] ?? 'Token validation failed.',
                'status' => 'error',
                'at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $tokenResponse['message'] ?? 'Invalid or expired page token for Instagram.',
                'steps' => $steps,
            ], 422);
        }
        $accessToken = $tokenResponse['access_token'];

        $pushStep([
            'key' => 'test.token',
            'message' => 'Page access token validated for Content Publishing.',
            'status' => 'ok',
            'at' => now()->toIso8601String(),
        ]);

        $carouselResult = $this->buildCarouselMediaFromRequest($request, $pushStep);
        if ($carouselResult instanceof JsonResponse) {
            return $carouselResult;
        }
        $carouselMedia = $carouselResult;

        $publishDate = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

        $post = PostService::create([
            'user_id' => $user->id,
            'account_id' => $ig->id,
            'social_type' => 'instagram',
            'type' => 'carousel',
            'source' => 'instagram_carousel_test',
            'title' => $request->input('caption', 'Engagyo carousel test'),
            'comment' => null,
            'image' => null,
            'video' => 0,
            'metadata' => json_encode(['carousel_media' => $carouselMedia]),
            'publish_date' => $publishDate,
        ]);

        $pushStep([
            'key' => 'test.post_created',
            'message' => 'Post row created (id '.$post->id.').',
            'status' => 'ok',
            'meta' => ['post_id' => $post->id],
            'at' => now()->toIso8601String(),
        ]);

        $preview = PostService::postTypeBody($post);
        $pushStep([
            'key' => 'test.preview_urls',
            'message' => 'Resolved public URLs for Meta (see meta).',
            'status' => 'ok',
            'meta' => ['carousel_items' => $preview['carousel_items'] ?? []],
            'at' => now()->toIso8601String(),
        ]);

        $onGraphStep = function (array $step) use ($pushStep): void {
            $pushStep($step);
        };

        if ($request->boolean('async')) {
            PublishInstagramPost::dispatch($post->id, $accessToken);
            $pushStep([
                'key' => 'test.queued',
                'message' => 'PublishInstagramPost job queued (no step-by-step Graph log in async mode).',
                'status' => 'ok',
                'at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'mode' => 'async',
                'post_id' => $post->id,
                'steps' => $steps,
            ]);
        }

        try {
            $instagramGraphService->publishPost($post->fresh(['instagramAccount']), $accessToken, $onGraphStep);
        } catch (\Throwable $e) {
            $post->refresh();
            $pushStep([
                'key' => 'test.exception',
                'message' => $e->getMessage(),
                'status' => 'error',
                'at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'mode' => 'sync',
                'post_id' => $post->id,
                'message' => $e->getMessage(),
                'steps' => $steps,
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
            'steps' => $steps,
            'post' => [
                'status' => $post->status,
                'response' => $post->response,
            ],
        ]);
    }

    /**
     * Legacy GET ?urls= / ?sync= for quick JSON tests.
     */
    private function publishFromQuery(Request $request): JsonResponse
    {
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
                'message' => 'At least two https URLs required. Pass ?urls=url1,url2',
            ], 422);
        }
        if (count($urls) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Instagram allows at most 10 carousel items.',
            ], 422);
        }

        $sub = Request::create($request->path(), 'POST', array_filter([
            'caption' => $request->query('caption', 'Engagyo carousel test'),
            'account_id' => $request->query('account_id'),
            'async' => $request->query('async'),
            'url_lines' => $urls,
        ], fn ($v) => $v !== null && $v !== ''));

        $sub->setUserResolver($request->getUserResolver());

        return $this->publish($sub, app(InstagramGraphService::class));
    }

    private function resolveInstagramAccount(Request $request, $user): InstagramAccount|JsonResponse
    {
        $igQuery = InstagramAccount::query()->where('user_id', $user->id);
        if ($request->filled('account_id')) {
            $igQuery->where('id', $request->integer('account_id'));
        }
        $ig = $igQuery->first();
        if (! $ig) {
            return response()->json([
                'success' => false,
                'message' => 'No Instagram account found. Connect Instagram or pass account_id (instagram_accounts.id).',
            ], 422);
        }

        return $ig;
    }

    /**
     * @param  callable(array<string, mixed>): void  $pushStep
     * @return array<int, array{type: string, path: string}>|JsonResponse
     */
    private function buildCarouselMediaFromRequest(Request $request, callable $pushStep): array|JsonResponse
    {
        $carouselMedia = [];

        $uploadDir = public_path('uploads/instagram-carousel-test');
        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $files = $request->file('media');
        if ($files !== null && ! is_array($files)) {
            $files = [$files];
        }
        $files = array_values(array_filter($files ?? []));

        foreach ($files as $idx => $file) {
            if (! $file || ! $file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid upload at index '.($idx + 1).'.',
                ], 422);
            }
            $mime = strtolower((string) $file->getMimeType());
            $isVideo = str_starts_with($mime, 'video/');
            $isImage = str_starts_with($mime, 'image/');
            if (! $isVideo && ! $isImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'File '.($idx + 1).' must be an image or video (got '.$mime.').',
                ], 422);
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if ($ext === '') {
                $ext = $isVideo ? 'mp4' : 'jpg';
            }
            $storedName = 'igct_'.uniqid('', true).'_'.$safeBase.'.'.$ext;
            $file->move($uploadDir, $storedName);
            $relPath = 'uploads/instagram-carousel-test/'.$storedName;
            $carouselMedia[] = ['type' => $isVideo ? 'video' : 'photo', 'path' => $relPath];

            $pushStep([
                'key' => 'test.file.'.($idx + 1),
                'message' => 'Saved upload to '.$relPath.' ('.($isVideo ? 'video' : 'image').').',
                'status' => 'ok',
                'at' => now()->toIso8601String(),
            ]);
        }

        $urlLines = $request->input('url_lines', []);
        if (is_string($urlLines)) {
            $urlLines = preg_split('/\r\n|\r|\n/', $urlLines) ?: [];
        }
        if (! is_array($urlLines)) {
            $urlLines = [];
        }
        foreach ($urlLines as $line) {
            $u = trim((string) $line);
            if ($u === '') {
                continue;
            }
            if (! filter_var($u, FILTER_VALIDATE_URL) || ! preg_match('#^https://#i', $u)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each URL line must be a valid https URL. Invalid: '.$u,
                ], 422);
            }
            $isVideo = (bool) preg_match('#\.(mp4|mov|m4v)(\?|$)#i', $u);
            $carouselMedia[] = ['type' => $isVideo ? 'video' : 'photo', 'path' => $u];
            $pushStep([
                'key' => 'test.remote_url',
                'message' => 'Added remote media URL ('.($isVideo ? 'video' : 'image').').',
                'status' => 'ok',
                'meta' => ['url' => strlen($u) > 120 ? substr($u, 0, 120).'…' : $u],
                'at' => now()->toIso8601String(),
            ]);
        }

        if (count($carouselMedia) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Add at least two images/videos (file uploads and/or https URL lines).',
            ], 422);
        }
        if (count($carouselMedia) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Instagram allows at most 10 carousel items.',
            ], 422);
        }

        return $carouselMedia;
    }
}
