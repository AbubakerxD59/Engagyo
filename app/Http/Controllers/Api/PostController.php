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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $apiKeyId = $request->input('api_key_id');
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
            Platform::FACEBOOK => $this->publishToFacebook($user, $apiKeyId, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt),
            Platform::PINTEREST => $this->publishToPinterest($user, $apiKeyId, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt),
            default => $this->errorResponse('Platform not supported for posting', 400),
        };
    }

    /**
     * Publish a post to Facebook.
     *
     * @param $user
     * @param int|null $apiKeyId
     * @param string $accountId
     * @param string $imageUrl
     * @param string $title
     * @param string $description
     * @param string|null $link
     * @param bool $publishNow
     * @param string|null $scheduledAt
     * @return \Illuminate\Http\JsonResponse
     */
    private function publishToFacebook($user, $apiKeyId, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt = null)
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
            'api_key_id' => $apiKeyId,
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
                    'status' => 'publishing',
                    'type' => $link ? 'link' : 'photo',
                    'created_at' => $post->created_at->toIso8601String(),
                ],
                'account' => $this->formatFacebookAccount($page),
            ], 'Post is being published to Facebook');
        }

        // Scheduled post response
        return $this->successResponse([
            'post' => [
                'id' => $post->id,
                'platform' => 'facebook',
                'status' => 'scheduled',
                'type' => $link ? 'link' : 'photo',
                'scheduled_at' => $post->publish_date,
                'created_at' => $post->created_at->toIso8601String(),
            ],
            'account' => $this->formatFacebookAccount($page),
        ], 'Post scheduled successfully for ' . date('M d, Y \a\t h:i A', strtotime($scheduledAt)));
    }

    /**
     * Publish a post to Pinterest.
     *
     * @param $user
     * @param int|null $apiKeyId
     * @param string $accountId
     * @param string $imageUrl
     * @param string $title
     * @param string $description
     * @param string|null $link
     * @param bool $publishNow
     * @param string|null $scheduledAt
     * @return \Illuminate\Http\JsonResponse
     */
    private function publishToPinterest($user, $apiKeyId, $accountId, $imageUrl, $title, $description, $link, $publishNow, $scheduledAt = null)
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

        // Use validateToken for proper error handling
        $tokenResponse = PinterestService::validateToken($board);
        if (!$tokenResponse['success']) {
            return $this->errorResponse($tokenResponse['message'] ?? 'Pinterest access token has expired. Please reconnect your Pinterest account.', 401);
        }
        $accessToken = $tokenResponse['access_token'];

        // Determine publish date and scheduled status
        $publishDate = $publishNow ? now() : $scheduledAt;
        $isScheduled = !$publishNow;

        // Create the post record
        $post = Post::create([
            'user_id' => $user->id,
            'api_key_id' => $apiKeyId,
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
                'board_id' => (string) $accountId,
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $imageUrl,
                ],
            ];

            // Add link if provided (destination URL for the pin)
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
                    'status' => 'publishing',
                    'type' => $link ? 'link' : 'photo',
                    'created_at' => $post->created_at->toIso8601String(),
                ],
                'account' => $this->formatPinterestAccount($board, $pinterest),
            ], 'Post is being published to Pinterest');
        }

        // Scheduled post response
        return $this->successResponse([
            'post' => [
                'id' => $post->id,
                'platform' => 'pinterest',
                'status' => 'scheduled',
                'type' => $link ? 'link' : 'photo',
                'scheduled_at' => $post->publish_date,
                'created_at' => $post->created_at->toIso8601String(),
            ],
            'account' => $this->formatPinterestAccount($board, $pinterest),
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
            ],
            'account' => $this->getAccountDetails($post),
        ];

        // Include error message if failed
        if ($post->status === -1 && $post->response) {
            $response['post']['error'] = $post->response;
        }

        return $this->successResponse($response);
    }

    /**
     * Get account details for a post.
     *
     * @param Post $post
     * @return array|null
     */
    private function getAccountDetails(Post $post): ?array
    {
        if ($post->social_type === 'facebook') {
            $page = Page::withoutGlobalScopes()->find($post->account_id);
            return $page ? $this->formatFacebookAccount($page) : null;
        }

        if ($post->social_type === 'pinterest') {
            $board = Board::withoutGlobalScopes()->with('pinterest')->find($post->account_id);
            return $board ? $this->formatPinterestAccount($board, $board->pinterest) : null;
        }

        return null;
    }

    /**
     * Format Facebook page account details.
     *
     * @param Page $page
     * @return array
     */
    private function formatFacebookAccount(Page $page): array
    {
        return [
            'type' => 'facebook_page',
            'page_id' => $page->page_id,
            'name' => $page->name,
            'profile_image' => $page->facebook?->profile_image
                ? url($page->facebook->profile_image)
                : null,
        ];
    }

    /**
     * Format Pinterest board account details.
     *
     * @param Board $board
     * @param $pinterest
     * @return array
     */
    private function formatPinterestAccount(Board $board, $pinterest): array
    {
        return [
            'type' => 'pinterest_board',
            'board_id' => $board->board_id,
            'board_name' => $board->name,
            'pinterest_account' => $pinterest ? [
                'username' => $pinterest->username,
                'profile_image' => $pinterest->profile_image
                    ? url($pinterest->profile_image)
                    : null,
            ] : null,
        ];
    }

    /**
     * Create and publish a video to Facebook or Pinterest.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function publishVideo(Request $request)
    {
        // Get supported platform values for validation
        $supportedPlatforms = array_map(
            fn(Platform $p) => $p->value,
            Platform::supported()
        );

        $validator = Validator::make($request->all(), [
            'platform' => ['required', Rule::in($supportedPlatforms)],
            'account_id' => 'required|string',
            'video_url' => 'required|url',
            'title' => 'required|string|max:500',
            'description' => 'nullable|string|max:2000',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $apiKeyId = $request->input('api_key_id');
        $platform = Platform::from($request->input('platform'));
        $accountId = $request->input('account_id');
        $videoUrl = $request->input('video_url');
        $title = $request->input('title');
        $description = $request->input('description', '');
        $scheduledAt = $request->input('scheduled_at');

        // Determine if this is a scheduled post or immediate publish
        $publishNow = empty($scheduledAt);

        return match ($platform) {
            Platform::FACEBOOK => $this->publishVideoToFacebook($user, $apiKeyId, $accountId, $videoUrl, $title, $description, $publishNow, $scheduledAt),
            Platform::PINTEREST => $this->publishVideoToPinterest($user, $apiKeyId, $accountId, $videoUrl, $title, $description, $publishNow, $scheduledAt),
            default => $this->errorResponse('Platform not supported for video posting', 400),
        };
    }

    /**
     * Publish a video to Facebook.
     *
     * @param $user
     * @param int|null $apiKeyId
     * @param string $accountId
     * @param string $videoUrl
     * @param string $title
     * @param string $description
     * @param bool $publishNow
     * @param string|null $scheduledAt
     * @return \Illuminate\Http\JsonResponse
     */
    private function publishVideoToFacebook($user, $apiKeyId, $accountId, $videoUrl, $title, $description, $publishNow, $scheduledAt = null)
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
        $tokenResponse = FacebookService::validateToken($page);
        if (!$tokenResponse['success']) {
            return $this->errorResponse($tokenResponse['message'] ?? 'Facebook page access token has expired. Please reconnect your Facebook account.', 401);
        }
        $accessToken = $tokenResponse['access_token'];

        // Determine publish date and scheduled status
        $publishDate = $publishNow ? now() : $scheduledAt;
        $isScheduled = !$publishNow;

        // Create the post record
        $post = Post::create([
            'user_id' => $user->id,
            'api_key_id' => $apiKeyId,
            'account_id' => $page->id,
            'social_type' => 'facebook',
            'type' => 'video',
            'source' => 'api',
            'title' => $title,
            'description' => $description,
            'video' => $videoUrl,
            'publish_date' => $publishDate,
            'status' => 0, // pending
            'scheduled' => $isScheduled ? 1 : 0,
        ]);

        if ($publishNow) {
            // Prepare video post data for Facebook
            $postData = [
                'description' => $title,
                'file_url' => $videoUrl,
            ];

            if (!empty($description)) {
                $postData['description'] = $title . "\n\n" . $description;
            }

            // Dispatch the job to publish
            PublishFacebookPost::dispatch(
                $post->id,
                $postData,
                $accessToken,
                'video'
            );

            return $this->successResponse([
                'post' => [
                    'id' => $post->id,
                    'platform' => 'facebook',
                    'status' => 'publishing',
                    'type' => 'video',
                    'created_at' => $post->created_at->toIso8601String(),
                ],
                'account' => $this->formatFacebookAccount($page),
            ], 'Video is being published to Facebook');
        }

        // Scheduled post response
        return $this->successResponse([
            'post' => [
                'id' => $post->id,
                'platform' => 'facebook',
                'status' => 'scheduled',
                'type' => 'video',
                'scheduled_at' => $post->publish_date,
                'created_at' => $post->created_at->toIso8601String(),
            ],
            'account' => $this->formatFacebookAccount($page),
        ], 'Video scheduled successfully for ' . date('M d, Y \a\t h:i A', strtotime($scheduledAt)));
    }

    /**
     * Publish a video to Pinterest.
     *
     * @param $user
     * @param int|null $apiKeyId
     * @param string $accountId
     * @param string $videoUrl
     * @param string $title
     * @param string $description
     * @param bool $publishNow
     * @param string|null $scheduledAt
     * @return \Illuminate\Http\JsonResponse
     */
    private function publishVideoToPinterest($user, $apiKeyId, $accountId, $videoUrl, $title, $description, $publishNow, $scheduledAt = null)
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

        // Use validateToken for proper error handling
        $tokenResponse = PinterestService::validateToken($board);
        if (!$tokenResponse['success']) {
            return $this->errorResponse($tokenResponse['message'] ?? 'Pinterest access token has expired. Please reconnect your Pinterest account.', 401);
        }
        $accessToken = $tokenResponse['access_token'];

        // Download video from URL and upload to S3
        try {
            $videoKey = $this->downloadAndUploadVideoToS3($videoUrl);
            if (!$videoKey) {
                return $this->errorResponse('Failed to download video from URL. Please ensure the URL is accessible and points to a valid video file.', 400);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Error processing video: ' . $e->getMessage(), 500);
        }

        // Determine publish date and scheduled status
        $publishDate = $publishNow ? now() : $scheduledAt;
        $isScheduled = !$publishNow;

        // Create the post record
        $post = Post::create([
            'user_id' => $user->id,
            'api_key_id' => $apiKeyId,
            'account_id' => $board->id,
            'social_type' => 'pinterest',
            'type' => 'video',
            'source' => 'api',
            'title' => $title,
            'description' => $description,
            'video' => $videoKey,
            'publish_date' => $publishDate,
            'status' => 0, // pending
            'scheduled' => $isScheduled ? 1 : 0,
        ]);

        if ($publishNow) {
            // Prepare video post data for Pinterest
            $postData = [
                'title' => $title,
                'board_id' => (string) $accountId,
                'video_key' => $videoKey,
            ];

            // Dispatch the job to publish
            PublishPinterestPost::dispatch(
                $post->id,
                $postData,
                $accessToken,
                'video'
            );

            return $this->successResponse([
                'post' => [
                    'id' => $post->id,
                    'platform' => 'pinterest',
                    'status' => 'publishing',
                    'type' => 'video',
                    'created_at' => $post->created_at->toIso8601String(),
                ],
                'account' => $this->formatPinterestAccount($board, $pinterest),
            ], 'Video is being published to Pinterest');
        }

        // Scheduled post response
        return $this->successResponse([
            'post' => [
                'id' => $post->id,
                'platform' => 'pinterest',
                'status' => 'scheduled',
                'type' => 'video',
                'scheduled_at' => $post->publish_date,
                'created_at' => $post->created_at->toIso8601String(),
            ],
            'account' => $this->formatPinterestAccount($board, $pinterest),
        ], 'Video scheduled successfully for ' . date('M d, Y \a\t h:i A', strtotime($scheduledAt)));
    }

    /**
     * Download video from URL and upload to S3.
     *
     * @param string $videoUrl
     * @return string|null S3 path or null on failure
     */
    private function downloadAndUploadVideoToS3(string $videoUrl): ?string
    {
        try {
            // Download video from URL
            $response = Http::timeout(300)->get($videoUrl);

            if (!$response->successful()) {
                return null;
            }

            // Get video content
            $videoContent = $response->body();

            // Generate a unique filename
            $extension = $this->getVideoExtensionFromUrl($videoUrl);
            $fileName = 'videos/' . time() . '_' . rand(1000, 9999) . '.' . $extension;

            // Upload to S3
            $uploaded = Storage::disk('s3')->put($fileName, $videoContent);

            if ($uploaded) {
                return $fileName;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error downloading/uploading video: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get video extension from URL.
     *
     * @param string $url
     * @return string
     */
    private function getVideoExtensionFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Default to mp4 if no extension found
        $validExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv'];
        if (in_array(strtolower($extension), $validExtensions)) {
            return strtolower($extension);
        }

        return 'mp4';
    }
}
