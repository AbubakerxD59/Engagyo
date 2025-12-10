<?php

namespace App\Services;

use Exception;
use App\Models\Tiktok as TikTokModel;
use App\Services\HttpService;
use TikTok\Authentication\Authentication;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TikTokService
{
    private $authentication;
    private $client;
    private $redirectUrl;
    private $scopes;
    private $baseUrl = "https://open.tiktokapis.com/v2/";

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
            }

            $tiktok->update($updateData);

            info("TikTok token refreshed successfully for ID: {$tiktok_id}");

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
            return [
                "success" => false,
                "message" => "TikTok API error: " . $errorMessage
            ];
        } catch (Exception $e) {
            // Handle any other exceptions
            $errorMessage = $e->getMessage();
            info("TikTok refresh token exception for ID {$tiktok_id}: {$errorMessage}");
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

            $access_token = $tiktok->access_token;
            $response = ["success" => true, "access_token" => $access_token];

            // If token is expired or invalid, try to refresh it
            if (!$tiktok->validToken()) {
                info("TikTok token expired for account ID: {$tiktok->id}. Attempting to refresh...");

                // Check if refresh token exists
                if (empty($tiktok->refresh_token)) {
                    return [
                        "success" => false,
                        "message" => "TikTok refresh token is missing. Please reconnect your TikTok account."
                    ];
                }

                $service = new TikTokService();
                $token = $service->refreshAccessToken($tiktok->refresh_token, $tiktok->id);

                if (isset($token["success"]) && $token["success"] && isset($token["access_token"])) {
                    $response = ["success" => true, "access_token" => $token["access_token"]];
                    info("TikTok token refreshed successfully for account ID: {$tiktok->id}");
                } else {
                    $errorMessage = $token["message"] ?? "Failed to refresh TikTok token. Please reconnect your account.";
                    $response = [
                        "success" => false,
                        "message" => $errorMessage
                    ];
                    info("TikTok token refresh failed for account ID: {$tiktok->id}. Error: " . $errorMessage);
                }
            }

            return $response;
        } catch (\Exception $e) {
            info("TikTok validateToken error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error validating TikTok token: " . $e->getMessage()
            ];
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
        $post_row = \App\Models\Post::find($id);
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
            $requestBody = [
                "post_info" => [
                    "privacy_level" => "SELF_ONLY", // Required: PUBLIC_TO_EVERYONE, MUTUAL_FOLLOW_FRIENDS, FOLLOWER_OF_CREATOR, SELF_ONLY
                    "title" => $post['title'] ?? "", // Optional: Video caption (max 2200 UTF-16 runes)
                ],
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
                        "status" => 1,
                        "published_at" => date("Y-m-d H:i:s"),
                        "response" => json_encode([
                            "success" => true,
                            "publish_id" => $publishId,
                            "message" => "Video published successfully to TikTok"
                        ])
                    ]);
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
        } catch (Exception $e) {
            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $e->getMessage()
                ])
            ]);
        } finally {
            // Always delete local file after processing
            $this->deleteLocalFile($localFilePath);
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
        $post_row = \App\Models\Post::find($id);
        $localFilePath = null;

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
            $requestBody = [
                "media_type" => "PHOTO",
                "post_mode" => "DIRECT_POST",
                "post_info" => [
                    "title" => $post['title'] ?? "",
                    "privacy_level" => "SELF_ONLY",
                ],
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
                        "status" => 1,
                        "published_at" => date("Y-m-d H:i:s"),
                        "response" => json_encode([
                            "success" => true,
                            "publish_id" => $publishId,
                            "message" => "Photo published successfully to TikTok"
                        ])
                    ]);
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
        } catch (Exception $e) {
            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $e->getMessage()
                ])
            ]);
        } finally {
            // Always delete local file after processing
            $this->deleteLocalFile($localFilePath);
        }
    }

    /**
     * Publish a link to TikTok
     * Note: TikTok doesn't support clickable links in posts.
     * This method posts the image with the link included in the caption.
     *
     * @param int $id Post ID
     * @param array $post Post data
     * @param string $access_token TikTok access token
     * @return void
     */
    public function link($id, $post, $access_token)
    {
        $header = array(
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . $access_token
        );
        $post_row = \App\Models\Post::find($id);
        $localFilePath = null;

        try {
            // Get link and image URL or S3 key
            $linkUrl = $post['link'] ?? null;
            $imageSource = $post['url'] ?? null;

            if (empty($imageSource)) {
                throw new Exception("Image URL is required for TikTok link post");
            }

            if (empty($linkUrl)) {
                throw new Exception("Link URL is required for TikTok link post");
            }

            // Download from S3 to local storage if needed
            $downloadResult = $this->downloadFromS3ToLocal($imageSource, 'image');

            if (!$downloadResult['success']) {
                throw new Exception("Failed to download image: " . ($downloadResult['message'] ?? "Unknown error"));
            }

            $imageUrl = $downloadResult['local_url'];
            $localFilePath = $downloadResult['local_path'];

            // Combine title with link (TikTok doesn't support clickable links, so we include it in the caption)
            $title = $post['title'] ?? "";
            $caption = trim($title . "\n\n" . $linkUrl);

            // Prepare the request body for TikTok Content Posting API
            // Since TikTok doesn't support clickable links, we post as a photo with the link in the caption
            $requestBody = [
                "media_type" => "PHOTO",
                "post_mode" => "DIRECT_POST",
                "post_info" => [
                    "title" => $caption,
                    "privacy_level" => "SELF_ONLY",
                ],
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
                        "status" => 1,
                        "published_at" => date("Y-m-d H:i:s"),
                        "response" => json_encode([
                            "success" => true,
                            "publish_id" => $publishId,
                            "message" => "Link post published successfully to TikTok (link included in caption - not clickable)",
                            "note" => "TikTok API does not support clickable links. The link has been included in the post caption."
                        ])
                    ]);
                } else {
                    // Check if there's an error in the response
                    $errorMessage = $response['error']['message'] ?? $response['error']['log_id'] ?? "Unknown error occurred";
                    throw new Exception("TikTok API error: " . $errorMessage);
                }
            } else {
                // Handle error response
                $errorMessage = "Failed to publish link post to TikTok";
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
        } catch (Exception $e) {
            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $e->getMessage()
                ])
            ]);
        } finally {
            // Always delete local file after processing
            $this->deleteLocalFile($localFilePath);
        }
    }
}
