<?php

namespace App\Services;

use CURLFile;
use Exception;
use App\Models\Post;
use App\Services\HttpService;
use DirkGroenen\Pinterest\Pinterest;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\RequestException;
use App\Models\Pinterest as PinterestModel;

class PinterestService
{
    private $pinterest;
    private $client;
    private $post;
    private $auth;
    private $header;
    private $scopes;
    private $baseUrl = "https://api.pinterest.com/v5/";
    private $response = "Post Published Successfully!";
    public function __construct()
    {
        $this->scopes = ['user_accounts:read', 'boards:read', 'pins:read', 'boards:write', 'pins:write'];
        $pinterest_id = env("PINTEREST_KEY");
        $pinterest_secret = env("PINTEREST_SECRET");
        $this->pinterest = new Pinterest($pinterest_id, $pinterest_secret);
        $this->client = new HttpService();
        $this->post = new Post();
        $this->auth = base64_encode("{$pinterest_id}:{$pinterest_secret}");
        $this->header = array("Content-Type" => "application/x-www-form-urlencoded", "Authorization" => "Basic " . $this->auth);
    }

    public function getLoginUrl()
    {
        $url = $this->pinterest->auth->getLoginUrl(route("pinterest.callback"), $this->scopes);
        return $url;
    }

    public function getOauthToken($code = null)
    {
        $data = array(
            "grant_type" => "authorization_code",
            "code" => (string) $code,
            "redirect_uri" => route("pinterest.callback"),
            "continuous_refresh" => true,
        );
        $token = $this->client->post($this->baseUrl . "oauth/token", $data,  $this->header);
        return $token;
    }

    public function refreshAccessToken($refresh_token, $pinterest_id)
    {
        try {
            // Validate input parameters
            if (empty($refresh_token)) {
                info("Pinterest refresh token error: Refresh token is empty for Pinterest ID: {$pinterest_id}");
                return [
                    "success" => false,
                    "message" => "Pinterest refresh token is missing. Please reconnect your Pinterest account."
                ];
            }

            // Find the Pinterest account first
            $pinterest = PinterestModel::find($pinterest_id);

            if (!$pinterest) {
                info("Pinterest refresh token error: Pinterest account not found for ID: {$pinterest_id}");
                return [
                    "success" => false,
                    "message" => "Pinterest account not found. Please reconnect your Pinterest account."
                ];
            }

            // Prepare refresh token request
            $data = array(
                "refresh_token" => $refresh_token,
                "grant_type" => "refresh_token",
            );

            // Make the API call to refresh the token
            $token = $this->client->post($this->baseUrl . "oauth/token", $data, $this->header);

            // Check if the API call was successful
            if (!$token) {
                info("Pinterest refresh token error: Empty response from Pinterest API for ID: {$pinterest_id}");
                return [
                    "success" => false,
                    "message" => "Failed to refresh Pinterest token. Empty response from Pinterest API."
                ];
            }

            // Check for error in response
            if (isset($token['error']) || isset($token['error_description'])) {
                $errorMessage = $token['error_description'] ?? $token['error'] ?? 'Unknown error';
                info("Pinterest refresh token error for ID {$pinterest_id}: {$errorMessage}");
                return [
                    "success" => false,
                    "message" => "Pinterest API error: " . $errorMessage
                ];
            }

            // Validate required fields in response
            if (!isset($token["access_token"])) {
                info("Pinterest refresh token error: No access_token in response for ID: {$pinterest_id}");
                return [
                    "success" => false,
                    "message" => "Invalid response from Pinterest API. Missing access token. Please reconnect your Pinterest account."
                ];
            }

            // Update the Pinterest account with new tokens
            $updateData = [
                "access_token" => $token["access_token"],
            ];

            // Only update optional fields if they exist in response
            if (isset($token["expires_in"])) {
                $updateData["expires_in"] = $token["expires_in"];
            }
            if (isset($token["refresh_token"])) {
                $updateData["refresh_token"] = $token["refresh_token"];
            }
            if (isset($token["refresh_token_expires_in"])) {
                $updateData["refresh_token_expires_in"] = $token["refresh_token_expires_in"];
            }

            $pinterest->update($updateData);

            info("Pinterest token refreshed successfully for ID: {$pinterest_id}");

            // Return success response with token data
            return [
                "success" => true,
                "access_token" => $token["access_token"],
                "expires_in" => $token["expires_in"] ?? null,
                "refresh_token" => $token["refresh_token"] ?? $refresh_token,
                "refresh_token_expires_in" => $token["refresh_token_expires_in"] ?? null,
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
            info("Pinterest refresh token HTTP error for ID {$pinterest_id}: {$errorMessage}");
            return [
                "success" => false,
                "message" => "Pinterest API error: " . $errorMessage
            ];
        } catch (Exception $e) {
            // Handle any other exceptions
            $errorMessage = $e->getMessage();
            info("Pinterest refresh token exception for ID {$pinterest_id}: {$errorMessage}");
            return [
                "success" => false,
                "message" => "Error refreshing Pinterest token: " . $errorMessage
            ];
        }
    }

    public function me($access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $me = $this->client->get($this->baseUrl . "user_account", [], $this->header);
        return $me;
    }

    public function getBoards($access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $boards = $this->client->get($this->baseUrl . "boards", [], $this->header);
        return $boards;
    }

    public function create($id, $postData, $access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);

        // Sanitize Pinterest API fields
        $postData = $this->sanitizePinData($postData);

        $publish = $this->client->postJson($this->baseUrl . "pins", $postData, $this->header);
        $post = $this->post->find($id);
        if (isset($publish['id'])) {
            $post->update([
                "post_id" => $publish["id"],
                "status" => 1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => true,
                    "post_id" => $publish["id"],
                    "message" => "Post published successfully to Pinterest"
                ]),
            ]);
            // Create success notification (background job)
            createSuccessNotification($post->user_id, "Post Published", "Your Pinterest post has been published successfully.");
        } else {
            $errorMessage = $this->extractErrorMessage($publish);
            $post->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            createErrorNotification($post->user_id, "Post Publishing Failed", "Failed to publish Pinterest post. " . $errorMessage);
        }
    }

    public function video($id, $post, $access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $post_row = Post::find($id);
        // step 1
        $response = $this->postIntent();
        if (isset($response["media_id"])) {
            $media_id = $response["media_id"];
            // step 2
            $file = $this->saveFileFromAws($post["video_key"]);
            if ($file["success"]) {
                // step 3
                $media_upload = $this->uploadToUrl($response, $file["fullPath"]);
                // step 4
                $media_status = $this->getUploadedMedia($media_id);
                if ($media_status["success"]) {
                    // step 5
                    $upload_video = $this->uploadVideo($post, $media_id);
                    if (isset($upload_video["id"])) {
                        $post_row->update([
                            "post_id" => $upload_video["id"],
                            "status" => 1,
                            "published_at" => date("Y-m-d H:i:s"),
                            "response" => json_encode([
                                "success" => true,
                                "post_id" => $upload_video["id"],
                                "message" => "Video published successfully to Pinterest"
                            ]),
                        ]);
                        // Create success notification (background job)
                        createSuccessNotification($post_row->user_id, "Post Published", "Your Pinterest video has been published successfully.");
                    } else {
                        $errorMessage = $this->extractErrorMessage($upload_video);
                        $post_row->update([
                            "status" => -1,
                            "published_at" => date("Y-m-d H:i:s"),
                            "response" => json_encode([
                                "success" => false,
                                "error" => $errorMessage
                            ])
                        ]);
                        // Create error notification (background job)
                        createErrorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish Pinterest video. " . $errorMessage);
                    }
                } else {
                    $errorMessage = $this->extractErrorMessage($media_status);
                    $post_row->update([
                        "status" => -1,
                        "published_at" => date("Y-m-d H:i:s"),
                        "response" => json_encode([
                            "success" => false,
                            "error" => $errorMessage
                        ])
                    ]);
                    // Create error notification (background job)
                    createErrorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish Pinterest video. " . $errorMessage);
                }
            } else {
                $errorMessage = $this->extractErrorMessage($file);
                $post_row->update([
                    "status" => -1,
                    "published_at" => date("Y-m-d H:i:s"),
                    "response" => json_encode([
                        "success" => false,
                        "error" => $errorMessage
                    ])
                ]);
                // Create error notification (background job)
                createErrorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish Pinterest video. " . $errorMessage);
            }
        } else {
            $errorMessage = $this->extractErrorMessage($response);
            $post_row->update([
                "status" => -1,
                "published_at" => date("Y-m-d H:i:s"),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            createErrorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish Pinterest video. " . $errorMessage);
        }
        removeFromS3($post["video_key"]);
        removeFile($post["video_key"]);
    }
    private function postIntent()
    {
        $response = $this->client->postJson($this->baseUrl . "media", ['media_type' => 'video'], $this->header);
        return $response;
    }
    private function saveFileFromAws($video_key)
    {
        try {
            $fileContents = Storage::disk("s3")->get($video_key);
            $localPublicPath = 'uploads/videos/' . basename($video_key);
            $fullPath = public_path($localPublicPath);
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $bytesWritten = file_put_contents($fullPath, $fileContents);
            return array(
                "success" => true,
                "directory" => $directory,
                "fullPath" => $fullPath
            );
        } catch (Exception $e) {
            return array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
    }
    private function uploadToUrl($parameters, $file)
    {
        $uploadUrl = $parameters["upload_url"];
        $multipartData = array();
        foreach ($parameters["upload_parameters"] as $name => $contents) {
            $multipartData[] = [
                'name' => $name,
                'contents' => $contents,
            ];
        }
        if (file_exists($file)) {
            $multipartData[] = [
                'name' => 'file',
                'contents' => fopen($file, 'r'),
                'filename' => basename($file),
                'headers' => [
                    'Content-Type' => mime_content_type($file) ?: 'video/mp4',
                ],
            ];
        } else {
            throw new \Exception("File not found at path: {$file}");
        }
        $response = $this->client->postMultipart($uploadUrl, $multipartData);
        return true;
    }
    private function getUploadedMedia($mediaId)
    {
        $mediaReady = false;
        $maxAttempts = 15;
        $attempt = 0;
        while (!$mediaReady && $attempt < $maxAttempts) {
            $attempt++;
            sleep(2);
            $response = $this->client->get($this->baseUrl . 'media/' . $mediaId, [], $this->header);
            $status = $response['status'] ?? 'unknown';
            if ($status === 'succeeded') {
                $mediaReady = true;
            } elseif ($status === 'failed') {
                $details = isset($response['details']) ? $response['details'] :  'Unknown error';
                $error =  "Video processing failed on Pinterest side. Status: " . $details;
            }
        }
        if ($mediaReady) {
            $response =  [
                "success" => true
            ];
        } else {
            $response = [
                "success" => false,
                "message" => isset($error) ? $error : "Video processing failed on Pinterest side."
            ];
        }
        return $response;
    }
    private function uploadVideo($postData, $media_id)
    {
        $payload = [
            "title" => $postData["title"],
            "board_id" => $postData["board_id"],
            "media_source" => [
                "source_type" => "video_id",
                "media_id" => $media_id,
                "cover_image_key_frame_time" => 1
            ]
        ];
        if (isset($postData['link']) && !empty($postData['link'])) {
            $payload['link'] = $postData['link'];
        }

        // Sanitize the payload
        $payload = $this->sanitizePinData($payload);
        info(json_encode($payload));

        $response = $this->client->postJson($this->baseUrl . "pins", $payload, $this->header);
        return $response;
    }


    public function delete($post)
    {
        $board = $post->board;

        if (!$board) {
            info("Pinterest delete error: Board not found for post ID: {$post->id}");
            return false;
        }

        $pinterest = $board->pinterest;

        if (!$pinterest) {
            info("Pinterest delete error: Pinterest account not found for post ID: {$post->id}");
            return false;
        }

        // Use validateToken for proper error handling
        $tokenResponse = self::validateToken($board);

        if (!$tokenResponse['success']) {
            info("Pinterest delete error: " . ($tokenResponse['message'] ?? 'Failed to validate token') . " for post ID: {$post->id}");
            return false;
        }

        $access_token = $tokenResponse['access_token'];
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);

        try {
            $response = $this->client->delete($this->baseUrl . "pins/" . $post->post_id, [], $this->header);
            return true;
        } catch (Exception $e) {
            info("Pinterest delete error for post ID {$post->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sanitize pin data to comply with Pinterest API limits.
     * - title: max 100 characters
     * - description: max 500 characters
     * - board_id: must be string
     *
     * @param array $data
     * @return array
     */
    private function sanitizePinData(array $data): array
    {
        // Trim title to max 100 characters (using 99 to be safe)
        if (isset($data['title']) && $data['title'] !== null) {
            $data['title'] = mb_strlen($data['title']) > 100 ? mb_substr($data['title'], 0, 99) : $data['title'];
        }

        // Trim description to max 500 characters (using 499 to be safe)
        if (isset($data['description']) && $data['description'] !== null) {
            $data['description'] = mb_strlen($data['description']) > 500 ? mb_substr($data['description'], 0, 499) : $data['description'];
        }

        // Ensure board_id is a string
        if (isset($data['board_id'])) {
            $data['board_id'] = (string) $data['board_id'];
        }

        return $data;
    }

    /**
     * Trim title to Pinterest's maximum allowed length.
     * Pinterest API allows max 100 characters for pin titles.
     *
     * @param string|null $title
     * @return string|null
     */
    private function trimTitle(?string $title): ?string
    {
        if ($title === null) {
            return null;
        }
        return mb_strlen($title) > 100 ? mb_substr($title, 0, 99) : $title;
    }

    /**
     * Validate and refresh Pinterest token if needed.
     *
     * @param object $board The Pinterest board with pinterest relationship
     * @return array
     */
    public static function validateToken($board)
    {
        try {
            // Check if board exists
            if (!$board) {
                return [
                    "success" => false,
                    "message" => "Pinterest board not found."
                ];
            }

            $pinterest = $board->pinterest;

            // Check if Pinterest account exists
            if (!$pinterest) {
                return [
                    "success" => false,
                    "message" => "Pinterest account not found. Please reconnect your Pinterest account."
                ];
            }

            // Check if access token exists
            if (empty($pinterest->access_token)) {
                return [
                    "success" => false,
                    "message" => "Pinterest access token is missing. Please reconnect your Pinterest account."
                ];
            }

            $access_token = $pinterest->access_token;
            $response = ["success" => true, "access_token" => $access_token];

            // If token is expired or invalid, try to refresh it
            if (!$pinterest->validToken()) {
                info("Pinterest token expired for account ID: {$pinterest->id}. Attempting to refresh...");

                // Check if refresh token exists
                if (empty($pinterest->refresh_token)) {
                    return [
                        "success" => false,
                        "message" => "Pinterest refresh token is missing. Please reconnect your Pinterest account."
                    ];
                }

                $service = new PinterestService();
                $token = $service->refreshAccessToken($pinterest->refresh_token, $pinterest->id);

                if (isset($token["success"]) && $token["success"] && isset($token["access_token"])) {
                    $response = ["success" => true, "access_token" => $token["access_token"]];
                    info("Pinterest token refreshed successfully for account ID: {$pinterest->id}");
                } elseif (isset($token["access_token"])) {
                    // Legacy response format (without success key)
                    $response = ["success" => true, "access_token" => $token["access_token"]];
                    info("Pinterest token refreshed successfully for account ID: {$pinterest->id}");
                } else {
                    $errorMessage = $token["message"] ?? "Failed to refresh Pinterest token. Please reconnect your account.";
                    $response = [
                        "success" => false,
                        "message" => $errorMessage
                    ];
                    info("Pinterest token refresh failed for account ID: {$pinterest->id}. Error: " . $errorMessage);
                }
            }

            return $response;
        } catch (\Exception $e) {
            info("Pinterest validateToken error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error validating Pinterest token: " . $e->getMessage()
            ];
        }
    }

    /**
     * Extract a clean, readable error message from Pinterest API response.
     *
     * @param mixed $response
     * @return string
     */
    private function extractErrorMessage($response): string
    {
        // If it's a string, return it directly
        if (is_string($response)) {
            return $response;
        }

        // If it's an array, try to extract the message
        if (is_array($response)) {
            // Check for common error message keys
            if (isset($response['message'])) {
                $message = $response['message'];

                // If message is also an array/object, try to get a string from it
                if (is_array($message)) {
                    return json_encode($message);
                }

                // Clean up the message - remove technical prefixes if present
                if (is_string($message)) {
                    // Remove common prefixes like "Invalid request: " etc.
                    $cleanMessage = preg_replace('/^(Invalid request:\s*)/i', '', $message);
                    return $cleanMessage ?: $message;
                }

                return (string) $message;
            }

            // Check for 'error' key
            if (isset($response['error'])) {
                if (is_string($response['error'])) {
                    return $response['error'];
                }
                if (is_array($response['error']) && isset($response['error']['message'])) {
                    return $response['error']['message'];
                }
            }

            // Check for 'error_description' key
            if (isset($response['error_description'])) {
                return $response['error_description'];
            }

            // If we have a code, include it in the message
            if (isset($response['code'])) {
                $code = $response['code'];
                $msg = $response['message'] ?? 'Unknown error';
                return "Error {$code}: {$msg}";
            }

            // Fallback: return JSON encoded response (limited length)
            $json = json_encode($response);
            return mb_strlen($json) > 200 ? mb_substr($json, 0, 200) . '...' : $json;
        }

        // Fallback for any other type
        return 'An unknown error occurred with Pinterest API.';
    }
}
