<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Notification;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;
use App\Classes\FacebookSDK\LaravelSessionPersistentDataHandler;
use App\Services\SocialMediaLogService;
use Illuminate\Support\Facades\Http;

class FacebookService
{
    private $facebook;
    private $post;
    private $helper;
    private $response = "Post Published Successfully!";
    private $logService;

    private function successNotification($userId, $title, $message, $post = null)
    {
        $body = ['type' => 'success', 'message' => $message];

        if ($post) {
            $post->loadMissing(['page.facebook']);
            $accountImage = null;
            $socialType = 'facebook';

            if ($post->page) {
                if (!empty($post->page->profile_image)) {
                    $accountImage = $post->page->profile_image;
                } elseif ($post->page->facebook && !empty($post->page->facebook->profile_image)) {
                    $accountImage = $post->page->facebook->profile_image;
                }
            }

            $body['social_type'] = $socialType;
            $body['account_image'] = $accountImage;
            $body['account_name'] = $post->page?->name ?? '';
            $body['account_username'] = $post->page?->facebook?->username ?? '';
        }

        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    private function errorNotification($userId, $title, $message, $post = null)
    {
        $body = ['type' => 'error', 'message' => $message];

        if ($post) {
            $post->loadMissing(['page.facebook']);
            $accountImage = null;
            $socialType = 'facebook';

            if ($post->page) {
                if (!empty($post->page->profile_image)) {
                    $accountImage = $post->page->profile_image;
                } elseif ($post->page->facebook && !empty($post->page->facebook->profile_image)) {
                    $accountImage = $post->page->facebook->profile_image;
                }
            }

            $body['social_type'] = $socialType;
            $body['account_image'] = $accountImage;
            $body['account_name'] = $post->page?->name ?? '';
            $body['account_username'] = $post->page?->facebook?->username ?? '';
        }

        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    /**
     * @param Post $post
     * @param array $response
     * @param string $postType link|photo|video|reel|story|content|quote
     */
    private function handlePostPublishResult(Post $post, array $response, string $postType = 'post')
    {
        $postTypeLabels = [
            'link' => 'link',
            'photo' => 'photo',
            'video' => 'video',
            'reel' => 'reel',
            'story' => 'story',
            'content' => 'post',
            'quote' => 'quote',
        ];

        $typeLabel = $postTypeLabels[$postType] ?? 'post';
        $typeLabelCapitalized = ucfirst($typeLabel);

        if ($response["success"]) {
            $graphNode = $response["data"]->getGraphNode();
            $post_id = $graphNode['id'];

            $post->update([
                "post_id" => $post_id,
                "status" => 1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => true,
                    "post_id" => $post_id,
                    "message" => "{$typeLabelCapitalized} published successfully to Facebook"
                ]),
            ]);

            $this->successNotification(
                $post->user_id,
                "Post Published",
                "Your Facebook {$typeLabel} has been published successfully.",
                $post
            );
        } else {
            $errorMessage = $response["message"] ?? "Failed to publish {$typeLabel} to Facebook.";

            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);

            $this->errorNotification(
                $post->user_id,
                "Post Publishing Failed",
                "Failed to publish Facebook {$typeLabel}. " . $errorMessage,
                $post
            );
        }
    }

    public function __construct()
    {
        $this->facebook = new Facebook([
            'app_id' => env("FACEBOOK_APP_ID"),
            'app_secret' => env("FACEBOOK_APP_SECRET"),
            'default_graph_version' => env("FACEBOOK_GRAPH_VERSION"),
            'persistent_data_handler' => new LaravelSessionPersistentDataHandler()
        ]);
        $this->helper = $this->facebook->getRedirectLoginHelper();
        $this->post = new Post();
        $this->logService = new SocialMediaLogService();
    }

    public function getAccessToken()
    {
        if (request('state')) {
            $this->helper->getPersistentDataHandler()->set('state', request('state'));
        }
        try {
            $access_token = $this->helper->getAccessToken();
            $getOAuth2Client = $this->facebook->getOAuth2Client();
            $tokenMetadata = $getOAuth2Client->debugToken($access_token);
            $access_token = $access_token->getValue();
            $response = [
                "success" => true,
                "data" => [
                    "metadata" => $tokenMetadata,
                    "access_token" => $access_token
                ],
            ];
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        }
        return $response;
    }

    public function validateAccessToken($access_token)
    {
        try {
            $tokenMetadata = $this->facebook->getOAuth2Client()->debugToken($access_token);
            $response = [
                "success" => true,
                "data" => $tokenMetadata,
            ];
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        }
        return $response;
    }

    public function refreshAccessToken($access_token, $page_id)
    {
        try {
            $page = Page::find($page_id);

            if (!$page) {
                return [
                    "success" => false,
                    "message" => "Facebook page not found. Please reconnect your Facebook account.",
                ];
            }

            $getOAuth2Client = $this->facebook->getOAuth2Client();

            $longLivedToken = $getOAuth2Client->getLongLivedAccessToken($access_token);

            if (!$longLivedToken) {
                return [
                    "success" => false,
                    "message" => "Failed to refresh access token. The token may have expired. Please reconnect your Facebook account.",
                ];
            }

            $tokenMetadata = $getOAuth2Client->debugToken($longLivedToken);

            if (!$tokenMetadata->getIsValid()) {
                return [
                    "success" => false,
                    "message" => "The refreshed token is invalid. Please reconnect your Facebook account.",
                ];
            }

            $newAccessToken = $longLivedToken->getValue();

            $expiresAt = $tokenMetadata->getField("data_access_expires_at");

            $page->update([
                "access_token" => $newAccessToken,
                "expires_in" => $expiresAt,
            ]);

            $response = [
                "success" => true,
                "data" => [
                    "metadata" => $tokenMetadata,
                    "access_token" => $newAccessToken
                ],
            ];
            $this->logService->logTokenRefresh('facebook', $page_id, 'success', 'Token refreshed successfully');
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => "Facebook API error: " . $error,
            ];
            $this->logService->logTokenRefresh('facebook', $page_id, 'failed', $error);
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => "Facebook SDK error: " . $error,
            ];
            $this->logService->logTokenRefresh('facebook', $page_id, 'failed', $error);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => "Unexpected error while refreshing token: " . $error,
            ];
            $this->logService->logTokenRefresh('facebook', $page_id, 'failed', $error);
        }
        return $response;
    }

    public function me($access_token)
    {
        try {
            $me = $this->facebook->get('/me?fields=id,name,email,picture', $access_token);
            $user = $me->getGraphUser();

            $response = [
                "success" => true,
                "data" => $user
            ];
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        }
        return $response;
    }

    public function pages($access_token)
    {
        try {
            $accounts = $this->facebook->get('/me/accounts', $access_token);
            $getGraphEdge = $accounts->getGraphEdge();
            $response = [
                "success" => true,
                "data" => $getGraphEdge
            ];
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        }
        return $response;
    }

    /**
     * List Facebook Pages with linked Instagram Business accounts (Graph nested fields).
     */
    public function meAccountsWithInstagram($access_token)
    {
        try {
            $path = '/me/accounts?fields=id,name,access_token,instagram_business_account{id,username,name,profile_picture_url}';
            $accounts = $this->facebook->get($path, $access_token);
            $getGraphEdge = $accounts->getGraphEdge();
            $response = [
                'success' => true,
                'data' => $getGraphEdge,
            ];
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $response = [
                'success' => false,
                'message' => $error,
            ];
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $response = [
                'success' => false,
                'message' => $error,
            ];
        }

        return $response;
    }

    public function pageProfileImage($access_token, $page_id)
    {
        $profile_picture = $this->facebook->get('/' . $page_id . '/picture?redirect=0&', $access_token);
        try {
            $getGraphEdge = $profile_picture->getGraphNode();
            $response = [
                "success" => true,
                "data" => $getGraphEdge
            ];
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        }
        return $response;
    }

    public function createLink($id, $access_token, $postData)
    {
        try {
            $post = Post::with("page.facebook")->findOrFail($id);
            $page_id = $post->page ? $post->page->page_id : null;
            $publish = $this->facebook->post('/' . $page_id . '/feed', $postData, $access_token);
            $response = [
                "success" => true,
                "data" => $publish
            ];
        } catch (FacebookResponseException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
        } catch (FacebookSDKException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
        }
        $post = Post::with("page.facebook")->find($id);
        $this->handlePostPublishResult($post, $response, 'link');
        return $response;
    }

    public function contentOnly($id, $access_token, $post)
    {
        try {
            $publish = $this->facebook->post('/me/feed', $post, $access_token);
            $response = [
                "success" => true,
                "data" => $publish
            ];
        } catch (FacebookResponseException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
        } catch (FacebookSDKException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
        }
        $post = Post::with("page.facebook")->find($id);
        $this->handlePostPublishResult($post, $response, 'content');
        return $response;
    }

    public function photo($id, $access_token, $postData)
    {
        try {
            $post = Post::with("page.facebook")->findOrFail($id);
            $page_id = $post->page ? $post->page->page_id : null;
            $publish = $this->facebook->post('/' . $page_id . '/photos', $postData, $access_token);
            $response = [
                "success" => true,
                "data" => $publish
            ];
            $this->logService->logPost('facebook', 'photo', $id, ['page_id' => $page_id], 'success');
        } catch (FacebookResponseException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
            $this->logService->logApiError('facebook', '/photos', $error, ['post_id' => $id, 'page_id' => $page_id ?? null]);
        } catch (FacebookSDKException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
            $this->logService->logApiError('facebook', '/photos', $error, ['post_id' => $id, 'page_id' => $page_id ?? null]);
        }
        $post = Post::with("page.facebook")->find($id);
        $this->handlePostPublishResult($post, $response, 'photo');
        return $response;
    }

    public function video($id, $access_token, $post)
    {
        $post_row = Post::with("page.facebook")->find($id);
        $page_id = $post_row->page ? $post_row->page->page_id : null;
        $this->logService->log('facebook', 'publish_video', 'page_id', ['page_id' => $page_id]);
        try {
            $publish = $this->facebook->post('/' . $page_id . '/videos', $post, $access_token);
            $response = [
                "success" => true,
                "data" => $publish
            ];
            $this->logService->logPost('facebook', 'video', $id, ['account_id' => $post_row->account_id], 'success');
        } catch (FacebookResponseException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
            $this->logService->logApiError('facebook', '/videos', $error, ['post_id' => $id, 'account_id' => $post_row->account_id ?? null]);
        } catch (FacebookSDKException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
            $this->logService->logApiError('facebook', '/videos', $error, ['post_id' => $id, 'account_id' => $post_row->account_id ?? null]);
        }
        $this->handlePostPublishResult($post_row, $response, 'video');
        if ($post_row->source !== 'test' && !empty($post_row->video)) {
            removeFile($post_row->video);
        }
        return $response;
    }

    public function story(int $id, string $access_token, array $postData): array
    {
        $post_row = Post::with('page.facebook')->findOrFail($id);
        $page_id = $post_row->page ? $post_row->page->page_id : null;

        if (empty($page_id)) {
            $msg = 'Facebook page not found for this post.';
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $mediaKind = (string) ($postData['media_kind'] ?? '');
        $isPhotoStory = $mediaKind === 'photo' || !empty($postData['photo_url']);

        if ($isPhotoStory) {
            $photoUrl = $postData['photo_url'] ?? null;
            if (empty($photoUrl)) {
                $msg = 'Story media URL is missing.';
                $this->handleStoryPublishFailure($post_row, $msg);
                return ['success' => false, 'message' => $msg];
            }

            $uploadResp = Http::asForm()
                ->acceptJson()
                ->timeout(120)
                ->post("{$this->graphBaseUrl()}/{$page_id}/photos", [
                    'access_token' => $access_token,
                    'url' => $photoUrl,
                    'published' => 'false',
                ]);

            if (! $uploadResp->successful()) {
                $msg = $this->formatHttpGraphError($uploadResp);
                $this->logService->logApiError('facebook', '/photos', $msg, ['post_id' => $id, 'page_id' => $page_id]);
                $this->handleStoryPublishFailure($post_row, $msg);
                return ['success' => false, 'message' => $msg];
            }

            $uploadJson = $uploadResp->json();
            $photoId = $uploadJson['id'] ?? null;
            if (! $photoId) {
                $msg = 'Invalid /photos response for story (missing photo id).';
                $this->handleStoryPublishFailure($post_row, $msg);
                return ['success' => false, 'message' => $msg];
            }

            $publishResp = Http::asForm()
                ->acceptJson()
                ->timeout(120)
                ->post("{$this->graphBaseUrl()}/{$page_id}/photo_stories", [
                    'access_token' => $access_token,
                    'photo_id' => $photoId,
                ]);

            if (! $publishResp->successful()) {
                $msg = $this->formatHttpGraphError($publishResp);
                $this->logService->logApiError('facebook', '/photo_stories', $msg, ['post_id' => $id, 'page_id' => $page_id, 'photo_id' => $photoId]);
                $this->handleStoryPublishFailure($post_row, $msg);
                return ['success' => false, 'message' => $msg];
            }

            $published = $publishResp->json();
            if (! ($published['success'] ?? false) || empty($published['post_id'])) {
                $msg = 'Facebook photo story publish was not successful: ' . $publishResp->body();
                $this->handleStoryPublishFailure($post_row, $msg);
                return ['success' => false, 'message' => $msg];
            }

            $storyPostId = (string) $published['post_id'];
            $this->logService->logPost('facebook', 'story', $id, ['account_id' => $post_row->account_id, 'page_id' => $page_id, 'photo_id' => $photoId], 'success');
            $this->handleStoryPublishSuccess($post_row, $storyPostId, ['photo_id' => (string) $photoId, 'raw' => $published]);

            return [
                'success' => true,
                'data' => [
                    'post_id' => $storyPostId,
                    'photo_id' => (string) $photoId,
                    'raw' => $published,
                ],
            ];
        }

        $videoUrl = $postData['video_url'] ?? null;
        if (empty($videoUrl)) {
            $msg = 'Story media URL is missing.';
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $graphBase = $this->graphBaseUrl();
        $startResp = Http::asJson()
            ->acceptJson()
            ->timeout(120)
            ->post("{$graphBase}/{$page_id}/video_stories?access_token=" . urlencode($access_token), [
                'upload_phase' => 'start',
            ]);

        if (! $startResp->successful()) {
            $msg = $this->formatHttpGraphError($startResp);
            $this->logService->logApiError('facebook', '/video_stories', $msg, ['post_id' => $id, 'phase' => 'start', 'page_id' => $page_id]);
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $start = $startResp->json();
        $videoId = $start['video_id'] ?? null;
        $uploadUrl = $start['upload_url'] ?? null;
        if (! $videoId || ! $uploadUrl) {
            $msg = 'Invalid /video_stories start response (missing video_id or upload_url).';
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $rupload = Http::withHeaders([
            'Authorization' => 'OAuth ' . $access_token,
            'file_url' => $videoUrl,
        ])
            ->timeout(600)
            ->post($uploadUrl);

        if (! $rupload->successful()) {
            $msg = 'Story video rupload failed: ' . ($rupload->body() ?: $rupload->reason());
            $this->logService->logApiError('facebook', 'rupload.facebook.com', $msg, ['post_id' => $id, 'video_id' => $videoId]);
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $ruploadJson = $rupload->json();
        if (! ($ruploadJson['success'] ?? false)) {
            $msg = 'Story video upload did not complete: ' . $rupload->body();
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $this->waitForVideoUploadPhaseComplete($graphBase, (string) $videoId, $access_token);

        $finishResp = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("{$graphBase}/{$page_id}/video_stories", [
                'access_token' => $access_token,
                'upload_phase' => 'finish',
                'video_id' => $videoId,
            ]);

        if (! $finishResp->successful()) {
            $msg = $this->formatHttpGraphError($finishResp);
            $this->logService->logApiError('facebook', '/video_stories', $msg, ['post_id' => $id, 'phase' => 'finish', 'video_id' => $videoId]);
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $finish = $finishResp->json();
        if (! ($finish['success'] ?? false) || empty($finish['post_id'])) {
            $msg = 'Facebook video story publish finish was not successful: ' . $finishResp->body();
            $this->handleStoryPublishFailure($post_row, $msg);
            return ['success' => false, 'message' => $msg];
        }

        $storyPostId = (string) $finish['post_id'];
        $this->logService->logPost('facebook', 'story', $id, ['account_id' => $post_row->account_id, 'page_id' => $page_id, 'video_id' => $videoId], 'success');
        $this->handleStoryPublishSuccess($post_row, $storyPostId, ['video_id' => (string) $videoId, 'raw' => $finish]);

        if ($post_row->source !== 'test' && ! empty($post_row->video)) {
            removeFile($post_row->video);
        }

        return [
            'success' => true,
            'data' => [
                'post_id' => $storyPostId,
                'video_id' => (string) $videoId,
                'raw' => $finish,
            ],
        ];
    }

    /**
     * Publish a Facebook Page Reel via Video API: start session → rupload (file_url) → finish.
     *
     * @param  array  $postData  file_url (required public HTTPS URL), description?, title?
     * @return array{success: bool, message?: string, data?: array}
     */
    public function reel(int $id, string $access_token, array $postData): array
    {
        $post_row = Post::with('page.facebook')->findOrFail($id);
        $page_id = $post_row->page ? $post_row->page->page_id : null;

        if (empty($page_id)) {
            $this->handleReelPublishFailure($post_row, 'Facebook page not found for this post.');
            return ['success' => false, 'message' => 'Facebook page not found for this post.'];
        }

        $fileUrl = $postData['file_url'] ?? null;
        if (empty($fileUrl)) {
            $this->handleReelPublishFailure($post_row, 'Missing file_url for reel (public video URL required).');
            return ['success' => false, 'message' => 'Missing file_url for reel (public video URL required).'];
        }

        $graphBase = $this->graphBaseUrl();
        $startUrl = "{$graphBase}/{$page_id}/video_reels?access_token=" . urlencode($access_token);

        $startResp = Http::asJson()
            ->acceptJson()
            ->timeout(120)
            ->post($startUrl, [
                'upload_phase' => 'start',
            ]);

        if (! $startResp->successful()) {
            $msg = $this->formatHttpGraphError($startResp);
            $this->logService->logApiError('facebook', '/video_reels', $msg, ['post_id' => $id, 'phase' => 'start', 'page_id' => $page_id]);
            $this->handleReelPublishFailure($post_row, $msg);

            return ['success' => false, 'message' => $msg];
        }

        $start = $startResp->json();
        if (! empty($start['error'])) {
            $msg = $start['error']['message'] ?? json_encode($start['error']);
            $this->logService->logApiError('facebook', '/video_reels', $msg, ['post_id' => $id, 'phase' => 'start']);
            $this->handleReelPublishFailure($post_row, $msg);

            return ['success' => false, 'message' => $msg];
        }

        $video_id = $start['video_id'] ?? null;
        $upload_url = $start['upload_url'] ?? null;
        if (! $video_id || ! $upload_url) {
            $msg = 'Invalid Facebook video_reels start response (missing video_id or upload_url).';
            $this->logService->logApiError('facebook', '/video_reels', $msg, ['post_id' => $id, 'body' => $startResp->body()]);
            $this->handleReelPublishFailure($post_row, $msg);

            return ['success' => false, 'message' => $msg];
        }

        $rupload = Http::withHeaders([
            'Authorization' => 'OAuth ' . $access_token,
            'file_url' => $fileUrl,
        ])
            ->timeout(600)
            ->post($upload_url);

        if (! $rupload->successful()) {
            $msg = 'Reel rupload failed: ' . ($rupload->body() ?: $rupload->reason());
            $this->logService->logApiError('facebook', 'rupload.facebook.com', $msg, ['post_id' => $id, 'video_id' => $video_id]);
            $this->handleReelPublishFailure($post_row, $msg);

            return ['success' => false, 'message' => $msg];
        }

        $ruploadJson = $rupload->json();
        if (! ($ruploadJson['success'] ?? false)) {
            $msg = 'Reel upload did not complete: ' . $rupload->body();
            $this->logService->logApiError('facebook', 'rupload.facebook.com', $msg, ['post_id' => $id, 'video_id' => $video_id]);
            $this->handleReelPublishFailure($post_row, $msg);

            return ['success' => false, 'message' => $msg];
        }

        $this->waitForVideoUploadPhaseComplete($graphBase, $video_id, $access_token);

        $finishPayload = [
            'access_token' => $access_token,
            'upload_phase' => 'finish',
            'video_id' => $video_id,
            'video_state' => 'PUBLISHED',
        ];
        if (! empty($postData['description'])) {
            $finishPayload['description'] = $postData['description'];
        }
        if (! empty($postData['title'])) {
            $finishPayload['title'] = $postData['title'];
        }

        $finishResp = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("{$graphBase}/{$page_id}/video_reels", $finishPayload);

        if (! $finishResp->successful()) {
            $msg = $this->formatHttpGraphError($finishResp);
            $this->logService->logApiError('facebook', '/video_reels', $msg, ['post_id' => $id, 'phase' => 'finish', 'video_id' => $video_id]);
            $this->handleReelPublishFailure($post_row, $msg);

            return ['success' => false, 'message' => $msg];
        }

        $finish = $finishResp->json();
        if (! empty($finish['error'])) {
            $msg = $finish['error']['message'] ?? json_encode($finish['error']);
            $this->handleReelPublishFailure($post_row, $msg);
            $this->logService->logApiError('facebook', '/video_reels', $msg, ['post_id' => $id, 'phase' => 'finish']);

            return ['success' => false, 'message' => $msg];
        }

        if (! ($finish['success'] ?? false)) {
            $msg = 'Facebook reel publish finish was not successful: ' . $finishResp->body();
            $this->handleReelPublishFailure($post_row, $msg);

            return ['success' => false, 'message' => $msg];
        }

        $fbPostId = isset($finish['post_id']) ? (string) $finish['post_id'] : (string) $video_id;

        $this->logService->logPost('facebook', 'reel', $id, ['account_id' => $post_row->account_id, 'page_id' => $page_id], 'success');
        $this->handleReelPublishSuccess($post_row, $fbPostId, (string) $video_id, $finish);

        if ($post_row->source !== 'test' && ! empty($post_row->video)) {
            removeFile($post_row->video);
        }

        return [
            'success' => true,
            'data' => [
                'post_id' => $fbPostId,
                'video_id' => (string) $video_id,
                'raw' => $finish,
            ],
        ];
    }

    private function graphBaseUrl(): string
    {
        $v = (string) env('FACEBOOK_GRAPH_VERSION', 'v21.0');
        $v = ltrim($v, '/');

        return 'https://graph.facebook.com/' . $v;
    }

    /**
     * @param  \Illuminate\Http\Client\Response  $response
     */
    private function formatHttpGraphError($response): string
    {
        $json = $response->json();
        if (is_array($json) && ! empty($json['error']['message'])) {
            return (string) $json['error']['message'];
        }

        $body = $response->body();

        return $body !== '' ? $body : ('HTTP ' . $response->status());
    }

    private function waitForVideoUploadPhaseComplete(string $graphBase, string $video_id, string $access_token): void
    {
        for ($i = 0; $i < 60; $i++) {
            $r = Http::acceptJson()
                ->timeout(30)
                ->get("{$graphBase}/{$video_id}", [
                    'fields' => 'status',
                    'access_token' => $access_token,
                ]);

            if (! $r->successful()) {
                return;
            }

            $uploadStatus = $r->json('status.uploading_phase.status');
            if ($uploadStatus === 'complete') {
                return;
            }
            if ($uploadStatus === 'error') {
                return;
            }

            usleep(500000);
        }
    }

    private function handleReelPublishSuccess(Post $post, string $postId, string $videoId, array $finish): void
    {
        $post->update([
            'post_id' => $postId,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => true,
                'post_id' => $postId,
                'video_id' => $videoId,
                'message' => 'Reel published successfully to Facebook',
                'raw' => $finish,
            ]),
        ]);

        $this->successNotification(
            $post->user_id,
            'Post Published',
            'Your Facebook reel has been published successfully.',
            $post
        );
    }

    private function handleReelPublishFailure(Post $post, string $message): void
    {
        $post->update([
            'status' => -1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => false,
                'error' => $message,
            ]),
        ]);

        $this->errorNotification(
            $post->user_id,
            'Post Publishing Failed',
            'Failed to publish Facebook reel. ' . $message,
            $post
        );
    }

    private function handleStoryPublishSuccess(Post $post, string $postId, array $meta = []): void
    {
        $post->update([
            'post_id' => $postId,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode(array_merge([
                'success' => true,
                'post_id' => $postId,
                'message' => 'Story published successfully to Facebook',
            ], $meta)),
        ]);

        $this->successNotification(
            $post->user_id,
            'Post Published',
            'Your Facebook story has been published successfully.',
            $post
        );
    }

    private function handleStoryPublishFailure(Post $post, string $message): void
    {
        $post->update([
            'status' => -1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => false,
                'error' => $message,
            ]),
        ]);

        $this->errorNotification(
            $post->user_id,
            'Post Publishing Failed',
            'Failed to publish Facebook story. ' . $message,
            $post
        );
    }

    public function postComment($post_id, $access_token, $comment)
    {
        try {
            $publish = $this->facebook->post('/' . $post_id . '/comments', ["message" => $comment], $access_token);
            $response = [
                "success" => true,
                "data" => $publish
            ];
        } catch (FacebookResponseException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
        } catch (FacebookSDKException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
        }
        return $response;
    }

    public function delete($post)
    {
        return $this->deleteFromFacebook($post->post_id, $post->page, $post->id);
    }

    /**
     * @param string $facebookPostId Graph post id (e.g. {page_id}_{post_id})
     */
    public function deleteFromFacebook(string $facebookPostId, Page $page, ?int $dbPostId = null): array
    {
        $logId = $dbPostId ?? $facebookPostId;
        try {
            $publish = $this->facebook->delete('/' . $facebookPostId, [], $page->access_token);
            $this->logService->logPostDeletion('facebook', $logId, 'success');
            return [
                "success" => true,
                "data" => $publish
            ];
        } catch (FacebookResponseException $e) {
            $error = $e->getMessage();
            $this->logService->logPostDeletion('facebook', $logId, 'failed');
            $this->logService->logApiError('facebook', '/delete', $error, ['post_id' => $logId]);
            return [
                "success" => false,
                "message" => $error
            ];
        } catch (FacebookSDKException $e) {
            $error = $e->getMessage();
            $this->logService->logPostDeletion('facebook', $logId, 'failed');
            $this->logService->logApiError('facebook', '/delete', $error, ['post_id' => $logId]);
            return [
                "success" => false,
                "message" => $error
            ];
        }
    }

    /**
     * @param array $metrics Default: post_impressions, post_impressions_unique, post_engaged_users
     * @return array impressions, reach, engaged_users
     */
    public function getPostInsights($postId, $accessToken, $metrics = ['post_impressions', 'post_impressions_unique', 'post_engaged_users'])
    {
        $result = [
            'impressions' => 0,
            'reach' => 0,
            'engaged_users' => 0,
        ];

        if (empty($postId) || empty($accessToken)) {
            return $result;
        }

        $metricParam = implode(',', $metrics);
        $endpoint = '/' . $postId . '/insights?metric=' . urlencode($metricParam) . '&period=lifetime';

        try {
            $response = $this->facebook->get($endpoint, $accessToken);
            $graphEdge = $response->getGraphEdge();

            foreach ($graphEdge as $insightNode) {
                $name = $insightNode->getField('name');
                $values = $insightNode->getField('values');
                $value = 0;
                if (!empty($values) && is_array($values)) {
                    $first = reset($values);
                    $value = is_array($first) && isset($first['value']) ? (int) $first['value'] : 0;
                } elseif ($values instanceof \Facebook\GraphNodes\GraphNode) {
                    $value = (int) $values->getField('value') ?? 0;
                }

                if ($name === 'post_impressions') {
                    $result['impressions'] = $value;
                } elseif ($name === 'post_impressions_unique') {
                    $result['reach'] = $value;
                } elseif ($name === 'post_engaged_users') {
                    $result['engaged_users'] = $value;
                }
            }
        } catch (FacebookResponseException $e) {
            return $result;
        } catch (FacebookSDKException $e) {
            return $result;
        }

        return $result;
    }

    /**
     * @return array followers, reach, video_views, engagements, *_by_day
     */
    public function getPageInsights($pageId, $accessToken, ?string $since = null, ?string $until = null)
    {
        $result = [
            'followers' => null,
            'reach' => null,
            'video_views' => null,
            'engagements' => null,
            'followers_by_day' => [],
            'reach_by_day' => [],
            'video_views_by_day' => [],
            'engagements_by_day' => [],
        ];

        if (empty($pageId) || empty($accessToken)) {
            return $result;
        }

        $until = $until ?: date('Y-m-d');
        $since = $since ?: date('Y-m-d', strtotime('-28 days', strtotime($until)));

        $metrics = [
            'page_follows',
            'page_total_media_view_unique',
            'page_video_views',
            'page_post_engagements',
        ];

        $metricParam = implode(',', $metrics);
        $endpoint = '/' . $pageId . '/insights?metric=' . urlencode($metricParam)
            . '&period=day&since=' . $since . '&until=' . $until;

        try {
            $response = $this->facebook->get($endpoint, $accessToken);
            $graphEdge = $response->getGraphEdge();

            $totals = [
                'page_follows' => null,
                'page_total_media_view_unique' => 0,
                'page_video_views' => 0,
                'page_post_engagements' => 0,
            ];

            foreach ($graphEdge as $insightNode) {
                $name = $insightNode->getField('name');
                if (!array_key_exists($name, $totals)) {
                    continue;
                }

                $values = $insightNode->getField('values');
                if (empty($values)) {
                    continue;
                }

                $byDayKey = match ($name) {
                    'page_follows' => 'followers_by_day',
                    'page_total_media_view_unique' => 'reach_by_day',
                    'page_video_views' => 'video_views_by_day',
                    'page_post_engagements' => 'engagements_by_day',
                    default => null,
                };
                if ($name === 'page_follows') {
                    $last = end($values);
                    if (count($last) > 0) {
                        $last = $last[0];
                        $value = $last->getField('value');
                        $totals['page_follows'] = $value ?? null;
                    }
                    foreach ($values as $item) {
                        $value = $item->getField('value');
                        $val = $value ?? null;
                        if ($byDayKey !== null && $val !== null) {
                            $endTime = $item->getField('end_time');
                            if ($endTime) {
                                $dateStr = \Carbon\Carbon::parse($endTime)->format('Y-m-d');
                                $result['followers_by_day'][$dateStr] = ($result['followers_by_day'][$dateStr] ?? 0) + (int) $val;
                            }
                        }
                    }
                } else {
                    foreach ($values as $item) {
                        $value = $item->getField('value');
                        $val = $value ?? null;
                        $totals[$name] += $val;
                        if ($byDayKey !== null && $val !== null) {
                            $endTime = $item->getField('end_time');
                            if ($endTime) {
                                $dateStr = \Carbon\Carbon::parse($endTime)->format('Y-m-d');
                                $result[$byDayKey][$dateStr] = ($result[$byDayKey][$dateStr] ?? 0) + (int) $val;
                            }
                        }
                    }
                }
            }

            $result['followers'] = $totals['page_follows'];
            $result['reach'] = $totals['page_total_media_view_unique'] ?: null;
            $result['video_views'] = $totals['page_video_views'] ?: null;
            $result['engagements'] = $totals['page_post_engagements'] ?: null;
            ksort($result['followers_by_day']);
            ksort($result['reach_by_day']);
            ksort($result['video_views_by_day']);
            ksort($result['engagements_by_day']);
        } catch (FacebookResponseException $e) {
            return $result;
        } catch (FacebookSDKException $e) {
            return $result;
        }

        return $result;
    }

    /**
     * @return array getPageInsights shape plus 'comparison' per metric
     */
    public function getPageInsightsWithComparison($pageId, $accessToken, ?string $since = null, ?string $until = null): array
    {
        $current = $this->getPageInsights($pageId, $accessToken, $since, $until);

        $until = $until ?: date('Y-m-d');
        $since = $since ?: date('Y-m-d', strtotime('-28 days', strtotime($until)));

        $sinceDt = \Carbon\Carbon::parse($since);
        $untilDt = \Carbon\Carbon::parse($until);
        $periodDays = $sinceDt->diffInDays($untilDt) + 1;

        $prevUntilDt = $sinceDt->copy()->subDay();
        $prevSinceDt = $prevUntilDt->copy()->subDays($periodDays - 1);
        $prevSince = $prevSinceDt->format('Y-m-d');
        $prevUntil = $prevUntilDt->format('Y-m-d');

        $previous = $this->getPageInsights($pageId, $accessToken, $prevSince, $prevUntil);

        $comparison = [];
        $metrics = ['followers', 'reach', 'video_views', 'engagements'];

        foreach ($metrics as $metric) {
            $curr = $current[$metric] ?? null;
            $prev = $previous[$metric] ?? null;

            $comparison[$metric] = ['change' => null, 'direction' => null];

            if ($curr === null || $prev === null || !is_numeric($curr) || !is_numeric($prev)) {
                continue;
            }

            $curr = (float) $curr;
            $prev = (float) $prev;

            $diff = $curr - $prev;

            if ($prev == 0) {
                $comparison[$metric] = [
                    'change' => $curr > 0 ? 100 : 0,
                    'direction' => $curr > 0 ? 'up' : null,
                    'diff' => $curr > 0 ? $diff : 0,
                ];
                continue;
            }

            $change = round((($curr - $prev) / $prev) * 100, 1);
            $comparison[$metric] = [
                'change' => $change,
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : null),
                'diff' => $diff,
            ];
        }

        $current['comparison'] = $comparison;
        return $current;
    }

    /**
     * Page /feed; first page only, limit capped at 100.
     */
    public function getPageFeed(string $pageId, string $accessToken, ?string $since = null, ?string $until = null, int $limit = 100): array
    {
        $posts = [];
        if (empty($pageId) || empty($accessToken)) {
            return $posts;
        }

        $until = $until ?: date('Y-m-d');
        $since = $since ?: date('Y-m-d', strtotime('-28 days', strtotime($until)));

        $sinceIso = $since . 'T00:00:00+0000';
        $untilIso = $until . 'T23:59:59+0000';

        $fields = 'id,message,created_time,full_picture,icon,is_popular,permalink_url,shares,status_type,story,comments.limit(0).summary(true)';
        $endpoint = '/' . $pageId . '/feed?fields=' . urlencode($fields)
            . '&since=' . urlencode($sinceIso)
            . '&until=' . urlencode($untilIso)
            . '&limit=' . min($limit, 100);

        try {
            $response = $this->facebook->get($endpoint, $accessToken);
            $graphEdge = $response->getGraphEdge();

            if ($graphEdge) {
                foreach ($graphEdge as $node) {
                    $sharesRaw = $node->getField('shares');
                    $shares = is_object($sharesRaw) && method_exists($sharesRaw, 'getField')
                        ? (int) ($sharesRaw->getField('count') ?? 0)
                        : (is_array($sharesRaw) ? (int) ($sharesRaw['count'] ?? 0) : (int) ($sharesRaw ?? 0));

                    $commentsRaw = $node->getField('comments');
                    $comments = 0;
                    if ($commentsRaw) {
                        if (is_object($commentsRaw) && method_exists($commentsRaw, 'getField')) {
                            $summary = $commentsRaw->getField('summary');
                            $comments = is_object($summary) ? (int) ($summary->getField('total_count') ?? 0) : (is_array($summary) ? (int) ($summary['total_count'] ?? 0) : 0);
                        } elseif (is_array($commentsRaw) && isset($commentsRaw['summary']['total_count'])) {
                            $comments = (int) $commentsRaw['summary']['total_count'];
                        }
                    }

                    $postId = $node->getField('id');
                    $post = [
                        'id' => $postId,
                        'post_id' => $postId,
                        'message' => $node->getField('message'),
                        'created_time' => $node->getField('created_time'),
                        'full_picture' => $node->getField('full_picture'),
                        'icon' => $node->getField('icon'),
                        'is_popular' => $node->getField('is_popular'),
                        'permalink_url' => $node->getField('permalink_url'),
                        'shares' => $shares,
                        'comments' => $comments,
                        'status_type' => $node->getField('status_type'),
                        'story' => $node->getField('story'),
                        'type' => $node->getField('type'),
                        'insights' => [],
                    ];
                    $posts[] = $post;
                }
            }
        } catch (FacebookResponseException $e) {
            return [];
        } catch (FacebookSDKException $e) {
            return [];
        }

        return $posts;
    }

    /**
     * @param string $insightsPreset default|sent_tab (same metrics; normalization below)
     */
    public function getPagePostsWithInsights(string $pageId, string $accessToken, ?string $since = null, ?string $until = null, string $insightsPreset = 'default'): array
    {
        $posts = $this->getPageFeed($pageId, $accessToken, $since, $until);
        if (empty($posts)) {
            return [];
        }

        $metrics = 'post_clicks,post_reactions_by_type_total,post_media_view,post_impressions_unique';
        $batchSize = 25;
        $offset = 0;

        foreach (array_chunk($posts, $batchSize) as $chunk) {
            $n = count($chunk);

            try {
                $insightsBatch = [];
                foreach ($chunk as $post) {
                    $insightsBatch[] = [
                        'method' => 'GET',
                        'relative_url' => $post['id'] . '/insights?metric=' . $metrics . '&period=lifetime',
                    ];
                }
                $params = ['batch' => json_encode($insightsBatch)];
                $response = $this->facebook->post('/', $params, $accessToken);
                $insightsResponses = $response->getDecodedBody();
                $insightsResponses = is_array($insightsResponses) ? array_values($insightsResponses) : [];
                foreach ($chunk as $i => $post) {
                    $postIndex = $offset + $i;
                    if (!isset($posts[$postIndex])) {
                        continue;
                    }
                    $insightsResp = $insightsResponses[$i] ?? null;
                    $insights = [];
                    if ($insightsResp) {
                        $code = $insightsResp['code'] ?? 0;
                        $respBody = $insightsResp['body'] ?? '{}';
                        $data = is_string($respBody) ? json_decode($respBody, true) : $respBody;
                        if ($code === 200 && isset($data['data']) && is_array($data['data'])) {
                            foreach ($data['data'] as $metricNode) {
                                $name = $metricNode['name'] ?? null;
                                $values = $metricNode['values'] ?? [];
                                if ($name && !empty($values)) {
                                    $first = reset($values);
                                    $val = $first['value'] ?? 0;
                                    if ($name === 'post_reactions_by_type_total' && is_array($val)) {
                                        $insights[$name] = (int) array_sum($val);
                                    } else {
                                        $insights[$name] = is_numeric($val) ? (int) $val : 0;
                                    }
                                }
                            }
                        }
                    }
                    $posts[$postIndex]['insights'] = $insights;
                }
            } catch (FacebookResponseException|FacebookSDKException $e) {
                for ($i = 0; $i < $n; $i++) {
                    if (isset($posts[$offset + $i])) {
                        $posts[$offset + $i]['insights'] = $posts[$offset + $i]['insights'] ?? [];
                    }
                }
            }

            try {
                $commentsBatch = [];
                foreach ($chunk as $post) {
                    $commentsBatch[] = [
                        'method' => 'GET',
                        'relative_url' => $post['id'] . '?fields=comments.limit(0).summary(true)',
                    ];
                }
                $params = ['batch' => json_encode($commentsBatch)];
                $response = $this->facebook->post('/', $params, $accessToken);
                $commentsResponses = $response->getDecodedBody();
                $commentsResponses = is_array($commentsResponses) ? array_values($commentsResponses) : [];
                foreach ($chunk as $i => $post) {
                    $postIndex = $offset + $i;
                    if (!isset($posts[$postIndex])) {
                        continue;
                    }
                    $commentsResp = $commentsResponses[$i] ?? null;
                    if ($commentsResp) {
                        $code = $commentsResp['code'] ?? 0;
                        $respBody = $commentsResp['body'] ?? '{}';
                        $data = is_string($respBody) ? json_decode($respBody, true) : $respBody;
                        if ($code === 200 && is_array($data)) {
                            $posts[$postIndex]['post_id'] = $data['id'] ?? $posts[$postIndex]['id'];
                            if (isset($data['comments']['summary']['total_count'])) {
                                $posts[$postIndex]['comments'] = (int) $data['comments']['summary']['total_count'];
                            }
                        }
                    }
                }
            } catch (FacebookResponseException|FacebookSDKException $e) {
            }

            $offset += $n;
        }

        foreach ($posts as &$post) {
            $insights = $post['insights'] ?? [];
            $clicks = (int) ($insights['post_clicks'] ?? 0);
            $reactions = (int) ($insights['post_reactions_by_type_total'] ?? 0);
                $impressions = (int) ($insights['post_media_view'] ?? 0);
                $reach = (int) ($insights['post_media_view'] ?? 0);
                $insights['post_clicks'] = $clicks;
                $insights['post_reactions'] = $reactions;
                $insights['post_impressions'] = $impressions;
                $insights['post_reach'] = $reach;
                $insights['post_engagement_rate'] = $impressions > 0
                    ? round((($clicks + $reactions) / $impressions) * 100, 2)
                    : 0;
                unset($insights['post_media_view'], $insights['post_impressions_unique'], $insights['post_reactions_by_type_total']);
            $post['insights'] = $insights;
        }
        unset($post);

        return $posts;
    }

    public static function validateToken($account)
    {
        try {
            if (!$account) {
                return [
                    "success" => false,
                    "message" => "Facebook account not found."
                ];
            }

            if (empty($account->access_token)) {
                return [
                    "success" => false,
                    "message" => "Facebook access token is missing. Please reconnect your Facebook account."
                ];
            }

            $access_token = $account->access_token;
            $response = ["success" => true, "access_token" => $access_token];

            if (!$account->validToken()) {

                $service = new FacebookService();
                $token = $service->refreshAccessToken($access_token, $account->id);

                if ($token["success"]) {
                    $data = $token["data"];
                    $response = ["success" => true, "access_token" => $data["access_token"]];
                } else {
                    $response = [
                        "success" => false,
                        "message" => $token["message"] ?? "Failed to refresh Facebook token. Please reconnect your account."
                    ];
                }
            }

            return $response;
        } catch (\Exception $e) {
            return [
                "success" => false,
                "message" => "Error validating Facebook token: " . $e->getMessage()
            ];
        }
    }
}
