<?php

namespace App\Http\Controllers\Api;

use App\Enums\Platform;
use App\Models\Post;
use App\Models\Page;
use App\Models\Board;
use App\Http\Controllers\BaseController;
use App\Jobs\PublishFacebookPost;
use App\Jobs\PublishPinterestPost;
use App\Services\FacebookService;
use App\Services\PinterestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PostController extends BaseController
{
    /**
     * Create and publish a post to Facebook or Pinterest.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Get supported platform values for validation
        $supportedPlatforms = array_map(
            fn(Platform $p) => $p->value,
            Platform::supported()
        );

        $validator = Validator::make($request->all(), [
            'platform' => ['required', Rule::in($supportedPlatforms)],
            'account_id' => 'required|string',
            'image_url' => 'required|url',
            'title' => 'required|string|max:500',
            'description' => 'nullable|string|max:2000',
            'link' => 'nullable|url',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $platform = Platform::from($request->input('platform'));
        $accountId = $request->input('account_id');
        $imageUrl = $request->input('image_url');
        $title = $request->input('title');
        $description = $request->input('description', '');
        $link = $request->input('link');
        $scheduledAt = $request->input('scheduled_at');

        // Determine if this is a scheduled post or immediate publish
        $publishNow = empty($scheduledAt);

        return match ($platform) {
            Platform::FACEBOOK => $this->publishToFacebook($user, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt),
            Platform::PINTEREST => $this->publishToPinterest($user, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt),
            default => $this->errorResponse('Platform not supported for posting', 400),
        };
    }

    /**
     * Publish a post to Facebook.
     *
     * @param $user
     * @param string $accountId
     * @param string $imageUrl
     * @param string $title
     * @param string $description
     * @param string|null $link
     * @param bool $publishNow
     * @param string|null $scheduledAt
     * @return \Illuminate\Http\JsonResponse
     */
    private function publishToFacebook($user, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt = null)
    {
        // Find the page by page_id (belongs to user)
        $page = Page::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('page_id', $accountId)
            ->first();

        if (!$page) {
            return $this->errorResponse('Facebook page not found or not connected to your account', 404);
        }

        // Validate access token
        if (!$page->validToken()) {
            return $this->errorResponse('Facebook page access token has expired. Please reconnect your Facebook account.', 401);
        }

        // Determine publish date and scheduled status
        $publishDate = $publishNow ? now() : $scheduledAt;
        $isScheduled = !$publishNow;

        // Create the post record
        $post = Post::create([
            'user_id' => $user->id,
            'account_id' => $page->id,
            'social_type' => 'facebook',
            'type' => $link ? 'link' : 'photo',
            'source' => 'api',
            'title' => $title,
            'description' => $description,
            'url' => $link,
            'image' => $imageUrl,
            'publish_date' => $publishDate,
            'status' => 0, // pending
            'scheduled' => $isScheduled ? 1 : 0,
        ]);

        if ($publishNow) {
            // Prepare post data for Facebook
            $message = $title;
            if (!empty($description)) {
                $message .= "\n\n" . $description;
            }

            $postData = [
                'message' => $message,
            ];

            // If there's a link, include it
            if ($link) {
                $postData['link'] = $link;
                $type = 'link';
            } else {
                // Photo post with image URL
                $postData['url'] = $imageUrl;
                $type = 'photo';
            }

            // Dispatch the job to publish
            PublishFacebookPost::dispatch(
                $post->id,
                $postData,
                $page->access_token,
                $type
            );

            return $this->successResponse([
                'post' => [
                    'id' => $post->id,
                    'platform' => 'facebook',
                    'account_id' => $accountId,
                    'account_name' => $page->name,
                    'status' => 'publishing',
                    'type' => $link ? 'link' : 'photo',
                    'created_at' => $post->created_at->toIso8601String(),
                ]
            ], 'Post is being published to Facebook');
        }

        // Scheduled post response
        return $this->successResponse([
            'post' => [
                'id' => $post->id,
                'platform' => 'facebook',
                'account_id' => $accountId,
                'account_name' => $page->name,
                'status' => 'scheduled',
                'type' => $link ? 'link' : 'photo',
                'scheduled_at' => $post->publish_date,
                'created_at' => $post->created_at->toIso8601String(),
            ]
        ], 'Post scheduled successfully for ' . date('M d, Y \a\t h:i A', strtotime($scheduledAt)));
    }

    /**
     * Publish a post to Pinterest.
     *
     * @param $user
     * @param string $accountId
     * @param string $imageUrl
     * @param string $title
     * @param string $description
     * @param string|null $link
     * @param bool $publishNow
     * @param string|null $scheduledAt
     * @return \Illuminate\Http\JsonResponse
     */
    private function publishToPinterest($user, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt = null)
    {
        // Find the board by board_id (belongs to user)
        $board = Board::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('board_id', $accountId)
            ->first();

        if (!$board) {
            return $this->errorResponse('Pinterest board not found or not connected to your account', 404);
        }

        // Get the Pinterest account
        $pinterest = $board->pinterest;
        if (!$pinterest) {
            return $this->errorResponse('Pinterest account not found', 404);
        }

        // Check if token is valid, refresh if needed
        $pinterestService = new PinterestService();
        if (!$pinterest->validToken()) {
            $token = $pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
            if (!$token || !isset($token['access_token'])) {
                return $this->errorResponse('Pinterest access token has expired. Please reconnect your Pinterest account.', 401);
            }
            $accessToken = $token['access_token'];
        } else {
            $accessToken = $pinterest->access_token;
        }

        // Determine publish date and scheduled status
        $publishDate = $publishNow ? now() : $scheduledAt;
        $isScheduled = !$publishNow;

        // Create the post record
        $post = Post::create([
            'user_id' => $user->id,
            'account_id' => $board->id,
            'social_type' => 'pinterest',
            'type' => $link ? 'link' : 'photo',
            'source' => 'api',
            'title' => $title,
            'description' => $description,
            'url' => $link,
            'image' => $imageUrl,
            'publish_date' => $publishDate,
            'status' => 0, // pending
            'scheduled' => $isScheduled ? 1 : 0,
        ]);

        if ($publishNow) {
            // Prepare post data for Pinterest
            $postData = [
                'title' => $title,
                'description' => $description ?? $title,
                'board_id' => $accountId,
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $imageUrl,
                ],
            ];

            // Add link if provided
            if ($link) {
                $postData['link'] = $link;
            }

            // Dispatch the job to publish
            PublishPinterestPost::dispatch(
                $post->id,
                $postData,
                $accessToken,
                'photo'
            );

            return $this->successResponse([
                'post' => [
                    'id' => $post->id,
                    'platform' => 'pinterest',
                    'account_id' => $accountId,
                    'board_name' => $board->name,
                    'status' => 'publishing',
                    'type' => $link ? 'link' : 'photo',
                    'created_at' => $post->created_at->toIso8601String(),
                ]
            ], 'Post is being published to Pinterest');
        }

        // Scheduled post response
        return $this->successResponse([
            'post' => [
                'id' => $post->id,
                'platform' => 'pinterest',
                'account_id' => $accountId,
                'board_name' => $board->name,
                'status' => 'scheduled',
                'type' => $link ? 'link' : 'photo',
                'scheduled_at' => $post->publish_date,
                'created_at' => $post->created_at->toIso8601String(),
            ]
        ], 'Post scheduled successfully for ' . date('M d, Y \a\t h:i A', strtotime($scheduledAt)));
    }

    /**
     * Get post status by ID.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request, $id)
    {
        $user = $request->user();

        $post = Post::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$post) {
            return $this->errorResponse('Post not found', 404);
        }

        // Determine status text based on status and scheduled flag
        $statusText = match (true) {
            $post->status === -1 => 'failed',
            $post->status === 1 => 'published',
            $post->scheduled === 1 && $post->status === 0 => 'scheduled',
            $post->status === 0 => 'pending',
            default => 'unknown',
        };

        $response = [
            'post' => [
                'id' => $post->id,
                'platform' => $post->social_type,
                'status' => $statusText,
                'status_code' => $post->status,
                'is_scheduled' => (bool) $post->scheduled,
                'title' => $post->title,
                'image' => $post->image,
                'post_id' => $post->post_id, // External post ID from platform
                'scheduled_at' => $post->scheduled ? $post->publish_date : null,
                'published_at' => $post->published_at,
                'created_at' => $post->created_at->toIso8601String(),
            ]
        ];

        // Include error message if failed
        if ($post->status === -1 && $post->response) {
            $response['post']['error'] = $post->response;
        }

        return $this->successResponse($response);
    }
}
