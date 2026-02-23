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
use App\Services\SocialMediaLogService;

class FacebookService
{
    private $facebook;
    private $post;
    private $helper;
    private $scopes;
    private $response = "Post Published Successfully!";
    private $logService;

    /**
     * Create a success notification
     */
    private function successNotification($userId, $title, $message, $post = null)
    {
        $body = ['type' => 'success', 'message' => $message];

        // Add account information if post is provided
        if ($post) {
            $post->loadMissing(['page.facebook']);
            $accountImage = null;
            $socialType = 'facebook';

            // Get account image from page
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
     * Create an error notification
     */
    private function errorNotification($userId, $title, $message, $post = null)
    {
        $body = ['type' => 'error', 'message' => $message];

        // Add account information if post is provided
        if ($post) {
            $post->loadMissing(['page.facebook']);
            $accountImage = null;
            $socialType = 'facebook';

            // Get account image from page
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
     * Handle post publish result - update post in database and create notification
     * This centralizes the logic for updating posts and creating notifications after publishing
     * 
     * @param Post $post The post model instance
     * @param array $response The response from Facebook API
     * @param string $postType The type of post (link, photo, video, content)
     * @return void
     */
    private function handlePostPublishResult(Post $post, array $response, string $postType = 'post')
    {
        $postTypeLabels = [
            'link' => 'link',
            'photo' => 'photo',
            'video' => 'video',
            'content' => 'post',
            'quote' => 'quote',
        ];

        $typeLabel = $postTypeLabels[$postType] ?? 'post';
        $typeLabelCapitalized = ucfirst($typeLabel);

        if ($response["success"]) {
            // Extract post_id from Facebook response
            $graphNode = $response["data"]->getGraphNode();
            $post_id = $graphNode['id'];

            // Update post in database
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

            // Create success notification
            $this->successNotification(
                $post->user_id,
                "Post Published",
                "Your Facebook {$typeLabel} has been published successfully.",
                $post
            );
        } else {
            // Handle failure case
            $errorMessage = $response["message"] ?? "Failed to publish {$typeLabel} to Facebook.";

            // Update post in database with error status
            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => json_encode([
                    "success" => false,
                    "error" => $errorMessage
                ])
            ]);

            // Create error notification
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
        // $this->scopes = [
        //     "business_management",
        //     "email",
        //     "pages_manage_engagement",
        //     "pages_manage_metadata",
        //     "pages_manage_posts",
        //     "pages_read_engagement",
        //     "pages_read_user_content",
        //     "pages_show_list",
        //     "public_profile",
        //     "read_insights",
        // ];
        $this->scopes = [
            'email',
            'public_profile',
            'pages_show_list',
            // 'pages_manage_engagement',
            // 'pages_manage_metadata',
            // 'pages_manage_posts',
            // 'pages_read_engagement',
            // 'pages_read_user_content',
        ];
        $this->post = new Post();
        $this->logService = new SocialMediaLogService();
    }

    public function getLoginUrl()
    {
        $config_id = ['config_id' => env("FACEBOOK_CONFIG_ID")];
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
            $this->logService->logTokenRefresh('facebook', $page_id, 'success', 'Token refreshed successfully');
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => "Facebook API error: " . $error,
            ];
            $this->logService->logTokenRefresh('facebook', $page_id, 'failed', $error);
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            $error = $e->getMessage();
            $response = [
                "success" => false,
                "message" => "Facebook SDK error: " . $error,
            ];
            $this->logService->logTokenRefresh('facebook', $page_id, 'failed', $error);
        } catch (\Exception $e) {
            // Catch any other unexpected errors
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
        // Remove video from S3 if API failed and post source is not "test"
        if ($post_row->source !== 'test' && !empty($post_row->video)) {
            // removeFromS3($post_row->video);
            removeFile($post_row->video);
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
            $this->logService->logPostDeletion('facebook', $post->id, 'success');
        } catch (FacebookResponseException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
            $this->logService->logPostDeletion('facebook', $post->id, 'failed');
            $this->logService->logApiError('facebook', '/delete', $error, ['post_id' => $post->id]);
        } catch (FacebookSDKException $e) {
            $error =  $e->getMessage();
            $response = [
                "success" => false,
                "message" => $error
            ];
            $this->logService->logPostDeletion('facebook', $post->id, 'failed');
            $this->logService->logApiError('facebook', '/delete', $error, ['post_id' => $post->id]);
        }
        return $response;
    }

    /**
     * Get post insights (impressions, reach, engaged users) from Facebook Graph API.
     *
     * @param string $postId Facebook post ID (e.g. {page_id}_{post_id})
     * @param string $accessToken Page access token with read_insights permission
     * @param array $metrics Metrics to fetch (default: post_impressions, post_impressions_unique, post_engaged_users)
     * @return array Parsed metrics or empty array on error. Keys: impressions, reach, engaged_users
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
            // Insights may be unavailable for pages with < 100 likes, or token lacks read_insights
            return $result;
        } catch (FacebookSDKException $e) {
            return $result;
        }

        return $result;
    }

    /**
     * Get page-level insights (followers, reach, video views, engagements, link clicks, CTR).
     * Some metrics may be deprecated by Meta - returns null for unavailable.
     *
     * @param string $pageId Facebook page ID (graph ID, not DB id)
     * @param string $accessToken Page access token with read_insights permission
     * @param string|null $since Start date Y-m-d (default: 28 days ago)
     * @param string|null $until End date Y-m-d (default: today)
     * @return array Keys: followers, reach, video_views, engagements, link_clicks, click_through_rate
     */
    public function getPageInsights($pageId, $accessToken, ?string $since = null, ?string $until = null)
    {
        $result = [
            'followers' => null,
            'reach' => null,
            'video_views' => null,
            'engagements' => null,
            'link_clicks' => null,
            'click_through_rate' => null,
        ];

        if (empty($pageId) || empty($accessToken)) {
            return $result;
        }

        $until = $until ?: date('Y-m-d');
        $since = $since ?: date('Y-m-d', strtotime('-28 days', strtotime($until)));

        $metrics = [
            'page_follows',  //alternate to page_fans
            'page_impressions_unique',
            'page_video_views',
            // 'page_engaged_users',
            // 'page_cta_clicks_logged_in_total',
        ];

        $metricParam = implode(',', $metrics);
        $endpoint = '/' . $pageId . '/insights?metric=' . urlencode($metricParam)
            . '&period=day&since=' . $since . '&until=' . $until;

        // try {
            $response = $this->facebook->get($endpoint, $accessToken);
            $graphEdge = $response->getGraphEdge();

            $totals = [
                'page_follows' => null,
                'page_impressions_unique' => 0,
                'page_video_views' => 0,
                // 'page_engaged_users' => 0,
                // 'page_cta_clicks_logged_in_total' => 0,
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

                if ($name === 'page_follows') {
                    $items = $values->getItems();
                    dd($items, end($items));
                    $last = end($values);
                    $totals['page_follows'] = is_array($last) && isset($last['value']) ? (int) $last['value'] : null;
                } else {
                    foreach ($values as $item) {
                        $val = is_array($item) && isset($item['value']) ? (int) $item['value'] : 0;
                        $totals[$name] += $val;
                    }
                }
            }
            dd($totals);

            $result['followers'] = $totals['page_follows'];
            $result['reach'] = $totals['page_impressions_unique'] ?: null;
            $result['video_views'] = $totals['page_video_views'] ?: null;
            $result['engagements'] =  null;
            $result['link_clicks'] =  null;
            // $result['engagements'] = $totals['page_engaged_users'] ?: null;
            // $result['link_clicks'] = $totals['page_cta_clicks_logged_in_total'] ?: null;

            if ($result['reach'] !== null && $result['reach'] > 0 && $result['link_clicks'] !== null) {
                $result['click_through_rate'] = round(($result['link_clicks'] / $result['reach']) * 100, 2);
            }
        // } catch (FacebookResponseException $e) {
        //     return $result;
        // } catch (FacebookSDKException $e) {
        //     return $result;
        // }

        return $result;
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
