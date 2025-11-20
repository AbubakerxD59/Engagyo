<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
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
    public function __construct()
    {
        $this->facebook = new Facebook([
            'app_id' => env("FACEBOOK_APP_ID"),
            'app_secret' => env("FACEBOOK_APP_SECRET"),
            'default_graph_version' => env("FACEBOOK_GRAPH_VERSION"),
            'persistent_data_handler' => new LaravelSessionPersistentDataHandler()
        ]);
        $this->helper = $this->facebook->getRedirectLoginHelper();
        $this->scopes = ['business_management', 'email', 'public_profile', 'pages_manage_metadata', 'pages_manage_posts', 'pages_read_engagement', 'pages_show_list', 'pages_manage_engagement', 'pages_read_user_content', 'read_insights', 'pages_manage_ads'];
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

    public function refreshAccessToken($access_token, $page_id)
    {
        try {
            $getOAuth2Client = $this->facebook->getOAuth2Client();
            $access_token = $getOAuth2Client->getLongLivedAccessToken($access_token);
            $tokenMetadata = $getOAuth2Client->debugToken($access_token);
            $access_token = $access_token->getValue();
            $page = Page::find($page_id)->first();
            $page->update([
                "access_token" => $access_token,
                "expires_in" => $tokenMetadata->getField("data_access_expires_at"),
            ]);
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

    public function createLink($id, $access_token, $post)
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
            $createLink = $response["data"];
            $graphNode = $createLink->getGraphNode();
            $post_id = $graphNode['id'];
            $post->update([
                "post_id" => $post_id,
                "status" => 1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => success_response()
            ]);
        } else {
            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => $response["message"]
            ]);
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
                "response" => success_response()
            ]);
        } else {
            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => $response["message"]
            ]);
        }
        return $response;
    }

    public function photo($id, $access_token, $post)
    {
        info('photo function');
        try {
            $publish = $this->facebook->post('/me/photos', $post, $access_token);
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
                "response" => success_response()
            ]);
        } else {
            $post->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => $response["message"]
            ]);
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
                "response" => success_response()
            ]);
        } else {
            $post_row->update([
                "status" => -1,
                "published_at" => date('Y-m-d H:i:s'),
                "response" => $response["message"]
            ]);
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
}
