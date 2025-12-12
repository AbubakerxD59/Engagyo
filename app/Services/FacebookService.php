<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Notification;
use Facebook\Facebook;
use Illuminate\Support\Facades\Storage;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;
use App\Classes\FacebookSDK\LaravelSessionPersistentDataHandler;

class FacebookService
{
    private $facebook;
    private $post;
    private $helper;
    private $scopes;
    private $response = "Post Published Successfully!";

    /**
     * Create a success notification
     */
    private function successNotification($userId, $title, $message)
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => ['type' => 'success', 'message' => $message],
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    /**
     * Create an error notification
     */
    private function errorNotification($userId, $title, $message)
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => ['type' => 'error', 'message' => $message],
            'is_read' => false,
            'is_system' => false,
        ]);
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
        $this->scopes = ['business_management', 'email', 'public_profile', 'pages_manage_metadata', 'pages_manage_posts', 'pages_read_engagement', 'pages_show_list', 'pages_manage_engagement', 'pages_read_user_content', 'read_insights'];
        $this->post = new Post();
    }

    public function getLoginUrl()
    {
        $url = $this->helper->getLoginUrl(route("facebook.callback"), $this->scopes);
        return $url;
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
            // When Graph returns an error
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
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
            // When Graph returns an error
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error,
            ];
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
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
            // Find the page first
            $page = Page::find($page_id);
            
            if (!$page) {
                return [
                    "success" => false,
                    "message" => "Facebook page not found. Please reconnect your Facebook account.",
                ];
            }

            $getOAuth2Client = $this->facebook->getOAuth2Client();
            
            // Try to get a long-lived access token
            $longLivedToken = $getOAuth2Client->getLongLivedAccessToken($access_token);
            
            if (!$longLivedToken) {
                return [
                    "success" => false,
                    "message" => "Failed to refresh access token. The token may have expired. Please reconnect your Facebook account.",
                ];
            }
            
            // Debug the new token to get metadata
            $tokenMetadata = $getOAuth2Client->debugToken($longLivedToken);
            
            // Check if token is valid
            if (!$tokenMetadata->getIsValid()) {
                return [
                    "success" => false,
                    "message" => "The refreshed token is invalid. Please reconnect your Facebook account.",
                ];
            }
            
            $newAccessToken = $longLivedToken->getValue();
            
            // Get expiration time
            $expiresAt = $tokenMetadata->getField("data_access_expires_at");
            
            // Update the page with new token
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
            
            info("Facebook token refreshed successfully for page ID: {$page_id}");
            
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            $error = $e->getMessage();
            info("Facebook token refresh error (FacebookResponseException) for page ID {$page_id}: " . $error);
            $response = [
                "success" => false,
                "message" => "Facebook API error: " . $error,
            ];
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            $error = $e->getMessage();
            info("Facebook token refresh error (FacebookSDKException) for page ID {$page_id}: " . $error);
            $response = [
                "success" => false,
                "message" => "Facebook SDK error: " . $error,
            ];
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            $error = $e->getMessage();
            info("Facebook token refresh error (Exception) for page ID {$page_id}: " . $error);
            $response = [
                "success" => false,
                "message" => "Unexpected error while refreshing token: " . $error,
            ];
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
        $post = $this->post->find($id);
        if ($response["success"]) {
            $createLink = $response["data"];
            $graphNode = $createLink->getGraphNode();
            $post_id = $graphNode['id'];
            $post->update([
                "post_id" => $post_id,
                "status" => 1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => true,
                    "post_id" => $post_id,
                    "message" => "Post published successfully to Facebook"
                ]),
            ]);
            // Create success notification (background job)
            $this->successNotification($post->user_id, "Post Published", "Your Facebook post has been published successfully.");
        } else {
            $errorMessage = $response["message"] ?? "Failed to publish post to Facebook.";
            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post->user_id, "Post Publishing Failed", "Failed to publish Facebook post. " . $errorMessage);
        }
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
        $post = $this->post->find($id);
        if ($response["success"]) {
            $contentOnly = $response["data"];
            $graphNode = $contentOnly->getGraphNode();
            $post_id = $graphNode['id'];
            $post->update([
                "post_id" => $post_id,
                "status" => 1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => true,
                    "post_id" => $post_id,
                    "message" => "Post published successfully to Facebook"
                ]),
            ]);
            // Create success notification (background job)
            $this->successNotification($post->user_id, "Post Published", "Your Facebook post has been published successfully.");
        } else {
            $errorMessage = $response["message"] ?? "Failed to publish post to Facebook.";
            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post->user_id, "Post Publishing Failed", "Failed to publish Facebook post. " . $errorMessage);
        }
        return $response;
    }

    public function photo($id, $access_token, $postData)
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
        $post = $this->post->find($id);
        if ($response["success"]) {
            $contentOnly = $response["data"];
            $graphNode = $contentOnly->getGraphNode();
            $post_id = $graphNode['id'];
            $post->update([
                "post_id" => $post_id,
                "status" => 1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => true,
                    "post_id" => $post_id,
                    "message" => "Photo published successfully to Facebook"
                ]),
            ]);
            // Create success notification (background job)
            $this->successNotification($post->user_id, "Post Published", "Your Facebook photo has been published successfully.");
        } else {
            $errorMessage = $response["message"] ?? "Failed to publish photo to Facebook.";
            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post->user_id, "Post Publishing Failed", "Failed to publish Facebook photo. " . $errorMessage);
        }
        return $response;
    }

    public function video($id, $access_token, $post)
    {
        $post_row = $this->post->find($id);
        try {
            $publish = $this->facebook->post('/' . $post_row->account_id . '/videos', $post, $access_token);
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
        if ($response["success"]) {
            $contentOnly = $response["data"];
            $graphNode = $contentOnly->getGraphNode();
            $post_id = $graphNode['id'];
            $post_row->update([
                "post_id" => $post_id,
                "status" => 1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => true,
                    "post_id" => $post_id,
                    "message" => "Video published successfully to Facebook"
                ]),
            ]);
            // Create success notification (background job)
            $this->successNotification($post_row->user_id, "Post Published", "Your Facebook video has been published successfully.");
        } else {
            $errorMessage = $response["message"] ?? "Failed to publish video to Facebook.";
            $post_row->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);
            // Create error notification (background job)
            $this->errorNotification($post_row->user_id, "Post Publishing Failed", "Failed to publish Facebook video. " . $errorMessage);
        }
        return $response;
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
        try {
            $page = $post->page;
            $publish = $this->facebook->delete('/' . $post->post_id, [], $page->access_token);
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

    public static function validateToken($account)
    {
        try {
            // Check if account exists
            if (!$account) {
                return [
                    "success" => false,
                    "message" => "Facebook account not found."
                ];
            }

            // Check if access token exists
            if (empty($account->access_token)) {
                return [
                    "success" => false,
                    "message" => "Facebook access token is missing. Please reconnect your Facebook account."
                ];
            }

            $access_token = $account->access_token;
            $response = ["success" => true, "access_token" => $access_token];

            // If token is expired or invalid, try to refresh it
            if (!$account->validToken()) {
                info("Facebook token expired for account ID: {$account->id}. Attempting to refresh...");
                
                $service = new FacebookService();
                $token = $service->refreshAccessToken($access_token, $account->id);
                
                if ($token["success"]) {
                    $data = $token["data"];
                    $response = ["success" => true, "access_token" => $data["access_token"]];
                    info("Facebook token refreshed successfully for account ID: {$account->id}");
                } else {
                    $response = [
                        "success" => false,
                        "message" => $token["message"] ?? "Failed to refresh Facebook token. Please reconnect your account."
                    ];
                    info("Facebook token refresh failed for account ID: {$account->id}. Error: " . ($token["message"] ?? "Unknown error"));
                }
            }

            return $response;

        } catch (\Exception $e) {
            info("Facebook validateToken error for account ID: " . ($account->id ?? 'unknown') . ". Error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error validating Facebook token: " . $e->getMessage()
            ];
        }
    }
}
