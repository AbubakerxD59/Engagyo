<?php

namespace App\Services;

use Exception;
use App\Models\Tiktok as TikTokModel;
use App\Models\Notification;
use App\Services\HttpService;
use TikTok\Authentication\Authentication;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\SocialMediaLogService;

class TikTokService
{
    private $authentication;
    private $client;
    private $redirectUrl;
    private $scopes;
    private $baseUrl = "https://open.tiktokapis.com/v2/";
    private $logService;

    /**
     * Create a success notification
     */
    private function successNotification($userId, $title, $message, $post = null)
    {
        $body = ['type' => 'success', 'message' => $message];

        // Add account information if post is provided
        if ($post) {
            $post->loadMissing(['tiktok']);
            $accountImage = null;
            $socialType = 'tiktok';

            // Get account image from tiktok relationship
            if ($post->tiktok && !empty($post->tiktok->profile_image)) {
                $accountImage = $post->tiktok->profile_image;
            }

            $body['social_type'] = $socialType;
            $body['account_image'] = $accountImage;
            $body['account_name'] = $post->tiktok?->display_name ?? $post->tiktok?->username ?? '';
            $body['account_username'] = $post->tiktok?->username ?? $post->tiktok?->display_name ?? '';
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
     * Create an error notification
     */
    private function errorNotification($userId, $title, $message, $post = null)
    {
        $body = ['type' => 'error', 'message' => $message];

        // Add account information if post is provided
        if ($post) {
            $post->loadMissing(['tiktok']);
            $accountImage = null;
            $socialType = 'tiktok';

            // Get account image from tiktok relationship
            if ($post->tiktok && !empty($post->tiktok->profile_image)) {
                $accountImage = $post->tiktok->profile_image;
            }

            $body['social_type'] = $socialType;
            $body['account_image'] = $accountImage;
            $body['account_name'] = $post->tiktok?->display_name ?? $post->tiktok?->username ?? '';
            $body['account_username'] = $post->tiktok?->username ?? $post->tiktok?->display_name ?? '';
        }

        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    public function __construct()
    {
        $this->authentication = new Authentication(array(
            'client_key' => env("TIKTOK_CLIENT_KEY"),
            'client_secret' => env("TIKTOK_CLIENT_SECRET")
        ));
        $this->redirectUrl = route("tiktok.callback");
        $this->scopes = array(
            "user.info.basic",
            "user.info.profile",
            "video.publish",
            "video.upload",
            "user.info.stats",
            "video.list"
        );
        $this->client = new HttpService();
        $this->logService = new SocialMediaLogService();
    }

    public function getLoginUrl()
    {
        $authenticationUrl = $this->authentication->getAuthenticationUrl($this->redirectUrl, $this->scopes);
        return $authenticationUrl;
    }

    public function getUserAccessToken($code)
    {
        try {
            $authenticationUrl = $this->authentication->getAccessTokenFromCode($code, $this->redirectUrl);
            $response = array(
                "success" => true,
                "data" => $authenticationUrl
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "error" => $e->getMessage()
            );
        }
        return $response;
    }

    public function refreshAccessToken($refresh_token, $tiktok_id)
    {
        try {
            // Validate input parameters
            if (empty($refresh_token)) {
                info("TikTok refresh token error: Refresh token is empty for TikTok ID: {$tiktok_id}");
                return [
                    "success" => false,
                    "message" => "TikTok refresh token is missing. Please reconnect your TikTok account."
                ];
            }

            // Find the TikTok account first
            $tiktok = TikTokModel::find($tiktok_id);

            if (!$tiktok) {
                info("TikTok refresh token error: TikTok account not found for ID: {$tiktok_id}");
                return [
                    "success" => false,
                    "message" => "TikTok account not found. Please reconnect your TikTok account."
                ];
            }

            $tokenRefresh = $this->authentication->getRefreshAccessToken($refresh_token);

            if (!$tokenRefresh) {
                info("TikTok refresh token error: Empty response from TikTok API for ID: {$tiktok_id}");
                return [
                    "success" => false,
                    "message" => "Failed to refresh TikTok token. Empty response from TikTok API."
                ];
            }

            // Check for error in response
            if (isset($tokenRefresh['error']) || isset($tokenRefresh['error_description'])) {
                $errorMessage = $tokenRefresh['error_description'] ?? $tokenRefresh['error'] ?? 'Unknown error';
                info("TikTok refresh token error for ID {$tiktok_id}: {$errorMessage}");
                return [
                    "success" => false,
                    "message" => "TikTok API error: " . $errorMessage
                ];
            }

            // Validate required fields in response
            if (!isset($tokenRefresh["access_token"])) {
                info("TikTok refresh token error: No access_token in response for ID: {$tiktok_id}");
                return [
                    "success" => false,
                    "message" => "Invalid response from TikTok API. Missing access token. Please reconnect your TikTok account."
                ];
            }

            // Update the TikTok account with new tokens
            $updateData = [
                "access_token" => $tokenRefresh["access_token"],
            ];

            // Only update optional fields if they exist in response
            if (isset($tokenRefresh["expires_in"])) {
                $updateData["expires_in"] = $tokenRefresh["expires_in"];
            }
            if (isset($tokenRefresh["refresh_token"])) {
                $updateData["refresh_token"] = $tokenRefresh["refresh_token"];
            }
            if (isset($tokenRefresh["refresh_token_expires_in"])) {
                $updateData["refresh_token_expires_in"] = $tokenRefresh["refresh_token_expires_in"];
            } elseif (isset($tokenRefresh["refresh_expires_in"])) {
                $updateData["refresh_token_expires_in"] = $tokenRefresh["refresh_expires_in"];
            }

            $tiktok->update($updateData);

            info("TikTok token refreshed successfully for ID: {$tiktok_id}");
            $this->logService->logTokenRefresh('tiktok', $tiktok_id, 'success', 'Token refreshed successfully');

            // Return success response with token data
            return [
                "success" => true,
                "access_token" => $tokenRefresh["access_token"],
                "expires_in" => $tokenRefresh["expires_in"] ?? null,
                "refresh_token" => $tokenRefresh["refresh_token"] ?? $refresh_token,
                "refresh_token_expires_in" => $tokenRefresh["refresh_token_expires_in"] ?? null,
            ];
        } catch (RequestException $e) {
            // Handle Guzzle HTTP exceptions
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['message'])) {
                    $errorMessage = $decoded['message'];
                } elseif (isset($decoded['error_description'])) {
                    $errorMessage = $decoded['error_description'];
                }
            }
            info("TikTok refresh token HTTP error for ID {$tiktok_id}: {$errorMessage}");
            $this->logService->logTokenRefresh('tiktok', $tiktok_id, 'failed', $errorMessage);
            return [
                "success" => false,
                "message" => "TikTok API error: " . $errorMessage
            ];
        } catch (Exception $e) {
            // Handle any other exceptions
            $errorMessage = $e->getMessage();
            info("TikTok refresh token exception for ID {$tiktok_id}: {$errorMessage}");
            $this->logService->logTokenRefresh('tiktok', $tiktok_id, 'failed', $errorMessage);
            return [
                "success" => false,
                "message" => "Error refreshing TikTok token: " . $errorMessage
            ];
        }
    }

    /**
     * Get user information from TikTok API
     *
     * @param string $access_token
     * @return array
     */
    public function me($access_token)
    {
        $header = array(
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . $access_token
        );

        try {
            $response = $this->client->get($this->baseUrl . "user/info/", [
                "fields" => "open_id,union_id,avatar_url,display_name,username"
            ], $header);

            if (isset($response['data']['user'])) {
                return $response['data']['user'];
            }

            return [];
        } catch (Exception $e) {
            info("TikTok get user info error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Query creator information from TikTok API
     * Required for Direct Post API compliance
     * Reference: https://developers.tiktok.com/doc/content-posting-api-reference-query-creator-info
     *
     * @param string $access_token TikTok access token
     * @return array
     */
    public function queryCreatorInfo($access_token)
    {
        $header = array(
            "Content-Type" => "application/json; charset=UTF-8",
            "Authorization" => "Bearer " . $access_token
        );

        try {
            $endpoint = $this->baseUrl . "post/publish/creator_info/query/";
            // API method is POST (GET returns errors / empty handled response).
            $response = $this->client->post($endpoint, [], $header);

            if ($response === null) {
                Log::warning('TikTok queryCreatorInfo: empty response (HTTP error or non-JSON).');
                return [
                    'success' => false,
                    'message' => 'No response from TikTok. Check logs or try again.',
                    'data' => null,
                ];
            }

            $errorCode = $response['error']['code'] ?? null;
            $errorMessage = $response['error']['message'] ?? '';

            if (($errorCode === 'ok' || $errorCode === null) && isset($response['data']) && is_array($response['data'])) {
                $data = $response['data'];

                // Normalize flags for callers that expect *_enabled (API returns *_disabled).
                if (!array_key_exists('comment_enabled', $data) && array_key_exists('comment_disabled', $data)) {
                    $data['comment_enabled'] = !$data['comment_disabled'];
                }
                if (!array_key_exists('duet_enabled', $data) && array_key_exists('duet_disabled', $data)) {
                    $data['duet_enabled'] = !$data['duet_disabled'];
                }
                if (!array_key_exists('stitch_enabled', $data) && array_key_exists('stitch_disabled', $data)) {
                    $data['stitch_enabled'] = !$data['stitch_disabled'];
                }

                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            $msg = $errorMessage !== '' ? $errorMessage : 'Failed to fetch creator info';
            if ($errorCode && $errorCode !== 'ok') {
                $msg = "TikTok API ({$errorCode}): {$msg}";
            }

            Log::warning('TikTok queryCreatorInfo failed', [
                'code' => $errorCode,
                'message' => $errorMessage,
                'response_keys' => array_keys($response),
            ]);

            return [
                'success' => false,
                'message' => $msg,
                'data' => null,
            ];
        } catch (Exception $e) {
            info("TikTok query creator info error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Validate and refresh TikTok token if needed.
     *
     * @param object $tiktok The TikTok account
     * @return array
     */
    public static function validateToken($tiktok)
    {
        try {
            // Check if TikTok account exists
            if (!$tiktok) {
                return [
                    "success" => false,
                    "message" => "TikTok account not found."
                ];
            }

            // Check if access token exists
            if (empty($tiktok->access_token)) {
                return [
                    "success" => false,
                    "message" => "TikTok access token is missing. Please reconnect your TikTok account."
                ];
            }

            $service = new TikTokService();

            if ($tiktok->validToken()) {
                return ["success" => true, "access_token" => $tiktok->access_token];
            }

            info("TikTok access token past stored expiry for account ID: {$tiktok->id}. Verifying with TikTok...");

            // Stored expires_in can be wrong or stale; avoid refresh (and refresh-token rotation) if the access token still works.
            $userInfo = $service->me($tiktok->access_token);
            if (!empty($userInfo)) {
                info("TikTok access token still valid for account ID: {$tiktok->id}; skipping OAuth refresh.");
                return ["success" => true, "access_token" => $tiktok->access_token];
            }

            if (empty($tiktok->refresh_token)) {
                return [
                    "success" => false,
                    "message" => "TikTok refresh token is missing. Please reconnect your TikTok account."
                ];
            }

            $oldRefresh = $tiktok->refresh_token;
            $token = $service->refreshAccessToken($oldRefresh, $tiktok->id);

            if (isset($token["success"]) && $token["success"] && isset($token["access_token"])) {
                info("TikTok token refreshed successfully for account ID: {$tiktok->id}");
                return ["success" => true, "access_token" => $token["access_token"]];
            }

            // Another request may have refreshed and rotated the refresh token; retry once with DB state.
            $tiktok->refresh();
            if (!empty($tiktok->refresh_token) && $tiktok->refresh_token !== $oldRefresh) {
                info("TikTok OAuth refresh retry for account ID: {$tiktok->id} after concurrent token update.");
                $token = $service->refreshAccessToken($tiktok->refresh_token, $tiktok->id);
                if (isset($token["success"]) && $token["success"] && isset($token["access_token"])) {
                    return ["success" => true, "access_token" => $token["access_token"]];
                }
            }

            $errorMessage = $token["message"] ?? "Failed to refresh TikTok token. Please reconnect your account.";
            info("TikTok token refresh failed for account ID: {$tiktok->id}. Error: " . $errorMessage);

            $hint = " If your TikTok session is old, disconnect and reconnect the account under Accounts.";
            if (stripos($errorMessage, "invalid") !== false || stripos($errorMessage, "expired") !== false) {
                $errorMessage .= $hint;
            }

            return [
                "success" => false,
                "message" => $errorMessage,
            ];
        } catch (\Exception $e) {
            info("TikTok validateToken error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error validating TikTok token: " . $e->getMessage()
            ];
        }
    }

    /**
     * Maps "Disclose post content" + sub-options to TikTok post_info (video and photo direct post).
     * brand_organic_toggle: creator's own business (in-app "Your brand").
     * brand_content_toggle: third-party / paid partnership (in-app "Branded content").
     *
     * @see https://developers.tiktok.com/doc/content-posting-api-reference-direct-post
     * @see https://developers.tiktok.com/doc/content-posting-api-reference-photo-post
     */
    private function applyTikTokDisclosureToPostInfo(array &$postInfo, array $post): void
    {
        if (empty($post['commercial_content_toggle'])) {
            return;
        }
        if (!empty($post['your_brand'])) {
            $postInfo['brand_organic_toggle'] = true;
        }
        if (!empty($post['branded_content'])) {
            $postInfo['brand_content_toggle'] = true;
        }
    }

    /**
     * Download file from S3 to local storage and return public URL
     *
     * @param string $s3KeyOrUrl S3 key or URL
     * @param string $type File type: 'video' or 'image'
     * @return array ['success' => bool, 'local_url' => string, 'local_path' => string]
     */
    private function downloadFromS3ToLocal($s3KeyOrUrl, $type = 'video')
    {
        try {
            // Check if it's already a URL or an S3 key
            $isUrl = filter_var($s3KeyOrUrl, FILTER_VALIDATE_URL);

            if ($isUrl) {
                // If it's a URL, check if it's from S3
                if (strpos($s3KeyOrUrl, 'amazonaws.com') !== false || strpos($s3KeyOrUrl, 's3.') !== false) {
                    // Extract S3 key from URL
                    $parsedUrl = parse_url($s3KeyOrUrl);
                    $s3Key = ltrim($parsedUrl['path'], '/');
                } else {
                    // It's already a public URL, return as is
                    return [
                        'success' => true,
                        'local_url' => $s3KeyOrUrl,
                        'local_path' => null
                    ];
                }
            } else {
                // It's an S3 key
                $s3Key = $s3KeyOrUrl;
            }

            // Download file from S3
            $fileContents = Storage::disk('s3')->get($s3Key);

            if (!$fileContents) {
                throw new Exception("Failed to download file from S3");
            }

            // Determine file extension
            $extension = pathinfo($s3Key, PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = $type === 'video' ? 'mp4' : 'jpg';
            }

            // Create local directory
            $localDir = $type === 'video' ? 'uploads/videos' : 'uploads/images';
            $localPublicPath = $localDir . '/' . uniqid('tiktok_', true) . '.' . $extension;
            $fullPath = public_path($localPublicPath);
            $directory = dirname($fullPath);

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save file locally
            $bytesWritten = file_put_contents($fullPath, $fileContents);

            if ($bytesWritten === false) {
                throw new Exception("Failed to save file to local storage");
            }

            // Generate public URL
            $localUrl = url($localPublicPath);

            return [
                'success' => true,
                'local_url' => $localUrl,
                'local_path' => $fullPath
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'local_url' => null,
                'local_path' => null
            ];
        }
    }

    /**
     * Delete local file if it exists
     *
     * @param string $filePath Full path to the file
     * @return void
     */
    private function deleteLocalFile($filePath)
    {
        if ($filePath && file_exists($filePath)) {
            try {
                unlink($filePath);
            } catch (Exception $e) {
                // Log error but don't throw
                Log::warning("Failed to delete local file: " . $e->getMessage());
            }
        }
    }

    /**
     * Publish a video to TikTok
     *
     * @param int $id Post ID
     * @param array $post Post data
     * @param string $access_token TikTok access token
     * @return void
     */
    public function video($id, $post, $access_token)
    {
        $header = array(
            "Content-Type" => "application/json; charset=UTF-8",
            "Authorization" => "Bearer " . $access_token
        );
        $post_row = \App\Models\Post::with("tiktok")->find($id);
        $localFilePath = null;

        try {
            // Get video URL or S3 key
            $videoSource = $post['file_url'] ?? null;

            if (empty($videoSource)) {
                throw new Exception("Video URL is required for TikTok video post");
            }

            // Download from S3 to local storage if needed
            $downloadResult = $this->downloadFromS3ToLocal($videoSource, 'video');

            if (!$downloadResult['success']) {
                throw new Exception("Failed to download video: " . ($downloadResult['message'] ?? "Unknown error"));
            }

            $videoUrl = $downloadResult['local_url'];
            $localFilePath = $downloadResult['local_path'];

            // Prepare the request body according to TikTok API documentation
            // Reference: https://developers.tiktok.com/doc/content-posting-api-reference-direct-post
            $postInfo = [
                "privacy_level" => $post['privacy_level'] ?? "SELF_ONLY", // Required: PUBLIC_TO_EVERYONE, MUTUAL_FOLLOW_FRIENDS, FOLLOWER_OF_CREATOR, SELF_ONLY
                "title" => $post['title'] ?? "", // Optional: Video caption (max 2200 UTF-16 runes)
            ];

            // Add interaction settings if provided (already "disable_*" semantics from ScheduleController)
            if (array_key_exists('disable_comment', $post)) {
                $postInfo["disable_comment"] = (bool) $post['disable_comment'];
            }
            if (array_key_exists('disable_duet', $post)) {
                $postInfo["disable_duet"] = (bool) $post['disable_duet'];
            }
            if (array_key_exists('disable_stitch', $post)) {
                $postInfo["disable_stitch"] = (bool) $post['disable_stitch'];
            }

            $this->applyTikTokDisclosureToPostInfo($postInfo, $post);

            $requestBody = [
                "post_info" => $postInfo,
                "source_info" => [
                    "source" => "PULL_FROM_URL", // Required: PULL_FROM_URL or FILE_UPLOAD
                    "video_url" => $videoUrl // Required for PULL_FROM_URL: Public-accessible URL
                ]
            ];

            // Make API call to TikTok Video Posting API
            // Endpoint: /v2/post/publish/video/init/
            $endpoint = $this->baseUrl . "post/publish/video/init/";
            $response = $this->client->postJson($endpoint, $requestBody, $header);

            // Check response according to TikTok API documentation
            // Response structure: { "data": { "publish_id": "...", "upload_url": "..." }, "error": { "code": "ok", "message": "", "log_id": "..." } }
            if ($response && isset($response['data'])) {
                $publishId = $response['data']['publish_id'] ?? null;
                $errorCode = $response['error']['code'] ?? null;

                // Check if error code is "ok" (success) or if publish_id exists
                if ($errorCode === "ok" && $publishId) {
                    // Post was successfully published
                    $post_row->update([
                        "post_id" => $publishId,
                        "status" => 1,
                        "published_at" => date("Y-m-d H:i:s"),
                        "response" => json_encode([
                            "success" => true,
                            "publish_id" => $publishId,
                            "message" => "Video published successfully to TikTok"
                        ])
                    ]);
                    // Create success notification (background job)
                    $this->successNotification($post_row->user_id, "Post Published", "Your TikTok video has been published successfully.", $post_row);
                    $this->logService->logPost('tiktok', 'video', $id, ['publish_id' => $publishId], 'success');
                } else {
                    // Error occurred - check error details
                    $errorMessage = $response['error']['message'] ?? "Unknown error occurred";
                    $logId = $response['error']['log_id'] ?? null;
                    $errorCode = $response['error']['code'] ?? "unknown_error";

                    throw new Exception("TikTok API error ({$errorCode}): {$errorMessage}" . ($logId ? " [Log ID: {$logId}]" : ""));
                }
            } else {
                // Handle error response when data is missing
                $errorMessage = "Failed to publish video to TikTok";
                if (isset($response['error'])) {
                    $errorCode = $response['error']['code'] ?? "unknown_error";
                    $errorMessage = $response['error']['message'] ?? $errorMessage;
                    $logId = $response['error']['log_id'] ?? null;
                    $errorMessage = "TikTok API error ({$errorCode}): {$errorMessage}" . ($logId ? " [Log ID: {$logId}]" : "");
                } elseif (isset($response['message'])) {
                    $errorMessage = $response['message'];
                }
                throw new Exception($errorMessage);
            }
        } catch (RequestException $e) {
            // Handle HTTP exceptions
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['error']['message'])) {
                    $errorMessage = $decoded['error']['message'];
                } elseif (isset($decoded['error']['log_id'])) {
                    $errorMessage = "TikTok API error (Log ID: " . $decoded['error']['log_id'] . ")";
                }
            }

            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish TikTok video. " . $errorMessage, $post_row);
            $this->logService->logPost('tiktok', 'video', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/video/init/', $errorMessage, ['post_id' => $id]);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish TikTok video. " . $errorMessage, $post_row);
            $this->logService->logPost('tiktok', 'video', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/video/init/', $errorMessage, ['post_id' => $id]);
        }
        // Remove video from S3 if API failed and post source is not "test"
        if ($post_row->source !== 'test' && !empty($post_row->video)) {
            // Check if video is an S3 key (not a URL)
            $isUrl = filter_var($post_row->video, FILTER_VALIDATE_URL);
            if (!$isUrl) {
                // removeFromS3($post_row->video);
                removeFile($post_row->video);
            }
        }
    }

    /**
     * Publish a photo to TikTok
     *
     * @param int $id Post ID
     * @param array $post Post data
     * @param string $access_token TikTok access token
     * @return void
     */
    public function photo($id, $post, $access_token)
    {
        $header = array(
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . $access_token
        );
        $post_row = \App\Models\Post::with("tiktok")->find($id);

        try {
            // Get image URL or S3 key
            $imageSource = $post['url'] ?? null;

            if (empty($imageSource)) {
                throw new Exception("Image URL is required for TikTok photo post");
            }

            // Download from S3 to local storage if needed
            $downloadResult = $this->downloadFromS3ToLocal($imageSource, 'image');

            if (!$downloadResult['success']) {
                throw new Exception("Failed to download image: " . ($downloadResult['message'] ?? "Unknown error"));
            }

            $imageUrl = $downloadResult['local_url'];
            $localFilePath = $downloadResult['local_path'];

            // Prepare the request body for TikTok Content Posting API
            $postInfo = [
                "title" => $post['title'] ?? "",
                "privacy_level" => $post['privacy_level'] ?? "SELF_ONLY",
            ];

            // Add interaction settings if provided (only comment for photos)
            if (array_key_exists('disable_comment', $post)) {
                $postInfo["disable_comment"] = (bool) $post['disable_comment'];
            }

            if (!empty($post['auto_add_music'])) {
                $postInfo["auto_add_music"] = true;
            }

            $this->applyTikTokDisclosureToPostInfo($postInfo, $post);

            $requestBody = [
                "media_type" => "PHOTO",
                "post_mode" => "DIRECT_POST",
                "post_info" => $postInfo,
                "source_info" => [
                    "source" => "PULL_FROM_URL",
                    "photo_cover_index" => 0,
                    "photo_images" => [$imageUrl]
                ]
            ];

            // Make API call to TikTok Content Posting API
            $endpoint = $this->baseUrl . "post/publish/content/init/";
            $response = $this->client->postJson($endpoint, $requestBody, $header);

            if ($response && isset($response['data'])) {
                $publishId = $response['data']['publish_id'] ?? null;

                if ($publishId) {
                    // Post was successfully published
                    $post_row->update([
                        "post_id" => $publishId,
                        "status" => 1,
                        "published_at" => date("Y-m-d H:i:s"),
                        "response" => json_encode([
                            "success" => true,
                            "publish_id" => $publishId,
                            "message" => "Photo published successfully to TikTok"
                        ])
                    ]);
                    // Create success notification (background job)
                    $this->successNotification($post_row->user_id, "Post Published", "Your TikTok photo has been published successfully.", $post_row);
                    $this->logService->logPost('tiktok', 'photo', $id, ['publish_id' => $publishId], 'success');
                } else {
                    // Check if there's an error in the response
                    $errorMessage = $response['error']['message'] ?? $response['error']['log_id'] ?? "Unknown error occurred";
                    throw new Exception("TikTok API error: " . $errorMessage);
                }
            } else {
                // Handle error response
                $errorMessage = "Failed to publish photo to TikTok";
                if (isset($response['error'])) {
                    $errorMessage = $response['error']['message'] ?? $response['error']['log_id'] ?? $errorMessage;
                } elseif (isset($response['message'])) {
                    $errorMessage = $response['message'];
                }
                throw new Exception($errorMessage);
            }
        } catch (RequestException $e) {
            // Handle HTTP exceptions
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['error']['message'])) {
                    $errorMessage = $decoded['error']['message'];
                } elseif (isset($decoded['error']['log_id'])) {
                    $errorMessage = "TikTok API error (Log ID: " . $decoded['error']['log_id'] . ")";
                }
            }

            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish TikTok photo. " . $errorMessage, $post_row);
            $this->logService->logPost('tiktok', 'photo', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/content/init/', $errorMessage, ['post_id' => $id]);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish TikTok photo. " . $errorMessage, $post_row);
            $this->logService->logPost('tiktok', 'photo', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/content/init/', $errorMessage, ['post_id' => $id]);
        } finally {
            // Always delete local file after processing
            $this->deleteLocalFile($localFilePath);
        }
    }

    /**
     * Delete a published TikTok post
     * Note: TikTok API may not support post deletion via third-party apps.
     * This method attempts deletion and handles errors gracefully.
     *
     * @param \App\Models\Post $post
     * @return bool
     */
    public function delete($post)
    {
        $tiktok = $post->tiktok;

        if (!$tiktok) {
            info("TikTok delete error: TikTok account not found for post ID: {$post->id}");
            return false;
        }

        // Use validateToken for proper error handling
        $tokenResponse = self::validateToken($tiktok);

        if (!$tokenResponse['success']) {
            info("TikTok delete error: " . ($tokenResponse['message'] ?? 'Failed to validate token') . " for post ID: {$post->id}");
            return false;
        }

        $access_token = $tokenResponse['access_token'];
        $header = array(
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . $access_token
        );

        // Check if post_id exists
        if (empty($post->post_id)) {
            info("TikTok delete error: Post ID is missing for post ID: {$post->id}");
            return false;
        }

        try {
            // Determine the endpoint based on post type
            // TikTok API v2 uses different endpoints for video vs content
            $endpoint = '';
            $requestBody = [
                "publish_id" => $post->post_id
            ];

            if ($post->type == "video") {
                // For video posts
                $endpoint = $this->baseUrl . "post/publish/video/delete/";
            } else {
                // For photo/link posts (content)
                $endpoint = $this->baseUrl . "post/publish/content/delete/";
            }

            // Attempt to delete via TikTok API
            $response = $this->client->postJson($endpoint, $requestBody, $header);

            // Check response
            if ($response && isset($response['error'])) {
                $errorCode = $response['error']['code'] ?? null;
                $errorMessage = $response['error']['message'] ?? "Unknown error";

                // If error code is "ok", deletion was successful
                if ($errorCode === "ok") {
                    info("TikTok post deleted successfully. Post ID: {$post->id}, Publish ID: {$post->post_id}");
                    $this->logService->logPostDeletion('tiktok', $post->id, 'success');
                    return true;
                } else {
                    // Log the error but don't fail completely
                    info("TikTok delete API error for post ID {$post->id}: {$errorMessage}");
                    $this->logService->logPostDeletion('tiktok', $post->id, 'failed');
                    $this->logService->logApiError('tiktok', $endpoint, $errorMessage, ['post_id' => $post->id]);
                    // Return false but don't throw - allow local deletion to proceed
                    return false;
                }
            } elseif ($response && !isset($response['error'])) {
                // Success response without error object
                info("TikTok post deleted successfully. Post ID: {$post->id}, Publish ID: {$post->post_id}");
                $this->logService->logPostDeletion('tiktok', $post->id, 'success');
                return true;
            } else {
                info("TikTok delete: Unexpected response format for post ID: {$post->id}");
                $this->logService->logPostDeletion('tiktok', $post->id, 'failed');
                return false;
            }
        } catch (RequestException $e) {
            // Handle HTTP exceptions
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['error']['message'])) {
                    $errorMessage = $decoded['error']['message'];
                }
            }
            info("TikTok delete HTTP error for post ID {$post->id}: {$errorMessage}");
            $this->logService->logPostDeletion('tiktok', $post->id, 'failed');
            $this->logService->logApiError('tiktok', $endpoint ?? '/delete', $errorMessage, ['post_id' => $post->id]);
            // Note: TikTok API may not support deletion, so we log but don't fail
            return false;
        } catch (Exception $e) {
            info("TikTok delete exception for post ID {$post->id}: " . $e->getMessage());
            $this->logService->logPostDeletion('tiktok', $post->id, 'failed');
            $this->logService->logApiError('tiktok', $endpoint ?? '/delete', $e->getMessage(), ['post_id' => $post->id]);
            // Note: TikTok API may not support deletion, so we log but don't fail
            return false;
        }
    }

    /**
     * Upload a video to TikTok as a draft (inbox)
     * Uses the Upload API to send video to TikTok's inbox for user review
     * Reference: https://developers.tiktok.com/doc/content-posting-api-get-started-upload-content
     *
     * @param int $id Post ID
     * @param array $post Post data
     * @param string $access_token TikTok access token
     * @param string $source Source type: 'FILE_UPLOAD' or 'PULL_FROM_URL' (default: 'PULL_FROM_URL')
     * @return void
     */
    public function uploadVideoDraft($id, $post, $access_token, $source = 'PULL_FROM_URL')
    {
        $header = array(
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . $access_token
        );
        $post_row = \App\Models\Post::with("tiktok")->find($id);
        $localFilePath = null;

        try {
            // Get video URL or S3 key
            $videoSource = $post['file_url'] ?? $post['video'] ?? null;

            if (empty($videoSource)) {
                throw new Exception("Video URL or file is required for TikTok video draft upload");
            }

            $requestBody = [];
            $uploadUrl = null;
            $publishId = null;

            if ($source === 'FILE_UPLOAD') {
                // For FILE_UPLOAD, we need to get file size first
                $downloadResult = $this->downloadFromS3ToLocal($videoSource, 'video');

                if (!$downloadResult['success']) {
                    throw new Exception("Failed to download video: " . ($downloadResult['message'] ?? "Unknown error"));
                }

                $localFilePath = $downloadResult['local_path'];

                if (!file_exists($localFilePath)) {
                    throw new Exception("Video file not found at local path");
                }

                $videoSize = filesize($localFilePath);
                $chunkSize = $videoSize; // For simplicity, we'll upload in one chunk
                $totalChunkCount = 1;

                // Prepare request body for FILE_UPLOAD
                $requestBody = [
                    "source_info" => [
                        "source" => "FILE_UPLOAD",
                        "video_size" => $videoSize,
                        "chunk_size" => $chunkSize,
                        "total_chunk_count" => $totalChunkCount
                    ]
                ];
            } else {
                // For PULL_FROM_URL, we can use the URL directly or download to local first
                // If it's already a public URL, use it directly
                $isUrl = filter_var($videoSource, FILTER_VALIDATE_URL);

                if ($isUrl && (strpos($videoSource, 'amazonaws.com') === false && strpos($videoSource, 's3.') === false)) {
                    // It's already a public URL, use it directly
                    $videoUrl = $videoSource;
                } else {
                    // Download from S3 to local storage and get public URL
                    $downloadResult = $this->downloadFromS3ToLocal($videoSource, 'video');

                    if (!$downloadResult['success']) {
                        throw new Exception("Failed to download video: " . ($downloadResult['message'] ?? "Unknown error"));
                    }

                    $videoUrl = $downloadResult['local_url'];
                    $localFilePath = $downloadResult['local_path'];
                }

                // Prepare request body for PULL_FROM_URL
                $requestBody = [
                    "source_info" => [
                        "source" => "PULL_FROM_URL",
                        "video_url" => $videoUrl
                    ]
                ];
            }

            // Make API call to TikTok Video Upload endpoint (inbox)
            // Endpoint: /v2/post/publish/inbox/video/init/
            $endpoint = $this->baseUrl . "post/publish/inbox/video/init/";
            $response = $this->client->postJson($endpoint, $requestBody, $header);

            // Check response
            if ($response && isset($response['data'])) {
                $publishId = $response['data']['publish_id'] ?? null;
                $uploadUrl = $response['data']['upload_url'] ?? null;
                $errorCode = $response['error']['code'] ?? null;

                if ($errorCode === "ok" && $publishId) {
                    // If using FILE_UPLOAD, we need to upload the file to the upload_url
                    if ($source === 'FILE_UPLOAD' && $uploadUrl && $localFilePath) {
                        $this->uploadFileToTikTok($uploadUrl, $localFilePath);
                    }

                    // Update post with publish_id and status
                    $post_row->update([
                        "post_id" => $publishId,
                        "status" => 0, // Status 0 = draft/pending (user needs to review in TikTok)
                        "response" => json_encode([
                            "success" => true,
                            "publish_id" => $publishId,
                            "message" => "Video uploaded to TikTok inbox as draft. User must review and post in TikTok app.",
                            "upload_type" => "draft"
                        ])
                    ]);

                    // Create success notification
                    $this->successNotification(
                        $post_row->user_id,
                        "Video Uploaded to Draft",
                        "Your TikTok video has been uploaded to your inbox as a draft. Please check your TikTok notifications to review and post it.",
                        $post_row
                    );
                    $this->logService->logDraft('tiktok', 'video', $id, ['publish_id' => $publishId], 'success');
                } else {
                    $errorMessage = $response['error']['message'] ?? "Unknown error occurred";
                    $logId = $response['error']['log_id'] ?? null;
                    throw new Exception("TikTok API error: {$errorMessage}" . ($logId ? " [Log ID: {$logId}]" : ""));
                }
            } else {
                $errorMessage = "Failed to upload video draft to TikTok";
                if (isset($response['error'])) {
                    $errorCode = $response['error']['code'] ?? "unknown_error";
                    $errorMessage = $response['error']['message'] ?? $errorMessage;
                    $logId = $response['error']['log_id'] ?? null;
                    $errorMessage = "TikTok API error ({$errorCode}): {$errorMessage}" . ($logId ? " [Log ID: {$logId}]" : "");
                }
                throw new Exception($errorMessage);
            }
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['error']['message'])) {
                    $errorMessage = $decoded['error']['message'];
                } elseif (isset($decoded['error']['log_id'])) {
                    $errorMessage = "TikTok API error (Log ID: " . $decoded['error']['log_id'] . ")";
                }
            }

            $post_row->update([
                "status" => -1,
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            $this->errorNotification($post_row->user_id, "Draft Upload Failed", "Failed to upload TikTok video draft. " . $errorMessage, $post_row);
            $this->logService->logDraft('tiktok', 'video', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/inbox/video/init/', $errorMessage, ['post_id' => $id]);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $post_row->update([
                "status" => -1,
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            $this->errorNotification($post_row->user_id, "Draft Upload Failed", "Failed to upload TikTok video draft. " . $errorMessage, $post_row);
            $this->logService->logDraft('tiktok', 'video', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/inbox/video/init/', $errorMessage, ['post_id' => $id]);
        } finally {
            // Always delete local file after processing
            $this->deleteLocalFile($localFilePath);
        }
    }

    /**
     * Upload a photo to TikTok as a draft (inbox)
     * Uses the Upload API to send photo to TikTok's inbox for user review
     * Reference: https://developers.tiktok.com/doc/content-posting-api-get-started-upload-content
     *
     * @param int $id Post ID
     * @param array $post Post data
     * @param string $access_token TikTok access token
     * @return void
     */
    public function uploadPhotoDraft($id, $post, $access_token)
    {
        $header = array(
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . $access_token
        );
        $post_row = \App\Models\Post::with("tiktok")->find($id);
        $localFilePath = null;

        try {
            // Get image URL or S3 key
            $imageSource = $post['url'] ?? $post['image'] ?? null;

            if (empty($imageSource)) {
                throw new Exception("Image URL is required for TikTok photo draft upload");
            }

            // For photos, we must use PULL_FROM_URL from a verified domain
            // Download from S3 to local storage if needed
            $downloadResult = $this->downloadFromS3ToLocal($imageSource, 'image');

            if (!$downloadResult['success']) {
                throw new Exception("Failed to download image: " . ($downloadResult['message'] ?? "Unknown error"));
            }

            $imageUrl = $downloadResult['local_url'];
            $localFilePath = $downloadResult['local_path'];

            // Prepare the request body for TikTok Content Posting API (Upload mode)
            // Note: For photos, post_mode must be MEDIA_UPLOAD and media_type must be PHOTO
            $requestBody = [
                "post_info" => [
                    "title" => $post['title'] ?? "",
                    "description" => $post['description'] ?? ""
                ],
                "source_info" => [
                    "source" => "PULL_FROM_URL",
                    "photo_cover_index" => 0,
                    "photo_images" => [$imageUrl] // Can be array of multiple images
                ],
                "post_mode" => "MEDIA_UPLOAD", // Required for draft upload
                "media_type" => "PHOTO" // Required for photo posts
            ];

            // Make API call to TikTok Content Posting API
            // Endpoint: /v2/post/publish/content/init/
            $endpoint = $this->baseUrl . "post/publish/content/init/";
            $response = $this->client->postJson($endpoint, $requestBody, $header);

            if ($response && isset($response['data'])) {
                $publishId = $response['data']['publish_id'] ?? null;
                $errorCode = $response['error']['code'] ?? null;

                if ($errorCode === "ok" && $publishId) {
                    // Update post with publish_id and status
                    $post_row->update([
                        "post_id" => $publishId,
                        "status" => 0, // Status 0 = draft/pending (user needs to review in TikTok)
                        "response" => json_encode([
                            "success" => true,
                            "publish_id" => $publishId,
                            "message" => "Photo uploaded to TikTok inbox as draft. User must review and post in TikTok app.",
                            "upload_type" => "draft"
                        ])
                    ]);

                    // Create success notification
                    $this->successNotification(
                        $post_row->user_id,
                        "Photo Uploaded to Draft",
                        "Your TikTok photo has been uploaded to your inbox as a draft. Please check your TikTok notifications to review and post it.",
                        $post_row
                    );
                    $this->logService->logDraft('tiktok', 'photo', $id, ['publish_id' => $publishId], 'success');
                } else {
                    $errorMessage = $response['error']['message'] ?? "Unknown error occurred";
                    $logId = $response['error']['log_id'] ?? null;
                    throw new Exception("TikTok API error: {$errorMessage}" . ($logId ? " [Log ID: {$logId}]" : ""));
                }
            } else {
                $errorMessage = "Failed to upload photo draft to TikTok";
                if (isset($response['error'])) {
                    $errorCode = $response['error']['code'] ?? "unknown_error";
                    $errorMessage = $response['error']['message'] ?? $errorMessage;
                    $logId = $response['error']['log_id'] ?? null;
                    $errorMessage = "TikTok API error ({$errorCode}): {$errorMessage}" . ($logId ? " [Log ID: {$logId}]" : "");
                }
                throw new Exception($errorMessage);
            }
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['error']['message'])) {
                    $errorMessage = $decoded['error']['message'];
                } elseif (isset($decoded['error']['log_id'])) {
                    $errorMessage = "TikTok API error (Log ID: " . $decoded['error']['log_id'] . ")";
                }
            }

            $post_row->update([
                "status" => -1,
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            $this->errorNotification($post_row->user_id, "Draft Upload Failed", "Failed to upload TikTok photo draft. " . $errorMessage, $post_row);
            $this->logService->logDraft('tiktok', 'photo', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/inbox/content/init/', $errorMessage, ['post_id' => $id]);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $post_row->update([
                "status" => -1,
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            $this->errorNotification($post_row->user_id, "Draft Upload Failed", "Failed to upload TikTok photo draft. " . $errorMessage, $post_row);
            $this->logService->logDraft('tiktok', 'photo', $id, [], 'failed');
            $this->logService->logApiError('tiktok', '/post/publish/inbox/content/init/', $errorMessage, ['post_id' => $id]);
        }
    }

    /**
     * Upload file to TikTok using PUT request
     * Used when source is FILE_UPLOAD
     *
     * @param string $uploadUrl The upload URL from TikTok API response
     * @param string $filePath Local file path to upload
     * @return void
     * @throws Exception
     */
    private function uploadFileToTikTok($uploadUrl, $filePath)
    {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            $fileSize = filesize($filePath);
            $fileHandle = fopen($filePath, 'rb');

            if (!$fileHandle) {
                throw new Exception("Failed to open file: {$filePath}");
            }

            // Read file content
            $fileContent = file_get_contents($filePath);
            fclose($fileHandle);

            // Determine content type
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $contentType = 'video/mp4'; // Default
            if ($extension === 'mov') {
                $contentType = 'video/quicktime';
            } elseif ($extension === 'avi') {
                $contentType = 'video/x-msvideo';
            }

            // Make PUT request to upload URL
            $response = $this->client->getClient()->put($uploadUrl, [
                'headers' => [
                    'Content-Range' => "bytes 0-{$fileSize}/{$fileSize}",
                    'Content-Type' => $contentType
                ],
                'body' => $fileContent
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new Exception("File upload failed with status code: {$statusCode}");
            }

            Log::info("File uploaded successfully to TikTok: {$uploadUrl}");
        } catch (Exception $e) {
            Log::error("Failed to upload file to TikTok: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get post status from TikTok API
     * Checks the status of an uploaded draft
     * Reference: https://developers.tiktok.com/doc/content-posting-api-get-post-status
     *
     * @param string $publishId The publish_id from upload response
     * @param string $access_token TikTok access token
     * @return array|null
     */
    public function getPostStatus($publishId, $access_token)
    {
        $header = array(
            "Content-Type" => "application/json; charset=UTF-8",
            "Authorization" => "Bearer " . $access_token
        );

        try {
            $requestBody = [
                "publish_id" => $publishId
            ];

            // Make API call to TikTok Get Post Status endpoint
            // Endpoint: /v2/post/publish/status/fetch/
            $endpoint = $this->baseUrl . "post/publish/status/fetch/";
            $response = $this->client->postJson($endpoint, $requestBody, $header);

            if ($response && isset($response['data'])) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'error' => $response['error'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? ['message' => 'Unknown error']
            ];
        } catch (Exception $e) {
            Log::error("Failed to get TikTok post status: " . $e->getMessage());
            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ];
        }
    }
}
