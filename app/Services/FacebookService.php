<?php

namespace App\Services;

use Facebook\Facebook;
use App\Services\HttpService;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;
use App\Classes\FacebookSDK\LaravelSessionPersistentDataHandler;

class FacebookService
{
    private $facebook;
    private $client;
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
        $this->client = new HttpService();
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
        // $params = array(
        //     "client_id" => env("FACEBOOK_APP_ID"),
        //     "client_secret" => env("FACEBOOK_APP_SECRET"),
        //     "redirect_uri" => route("facebook.callback"),
        //     "code" => request('code'),
        // );
        // $access_token = $this->facebook->sendRequest('GET', '/oauth/access_token/?' . http_build_query($params));
        $access_token = $this->helper->getAccessToken();
        $getOAuth2Client = $this->facebook->getOAuth2Client();
        $tokenMetadata = $getOAuth2Client->debugToken($access_token);
        $validate = $tokenMetadata->validateExpiration();
        $getLongLivedAccessToken = $getOAuth2Client->getLongLivedAccessToken($access_token);
        dd($access_token, $tokenMetadata, $valid, $getLongLivedAccessToken);
        try {
            $tokenMetadata->validateExpiration();
            if (!$access_token->isLongLived()) {
                try {
                    $access_token = $getOAuth2Client->getLongLivedAccessToken($access_token);
                    $access_token = $access_token->getValue();
                } catch (FacebookSDKException $e) {
                    $e->getMessage();
                }
            }
            $response = [
                "success" => true,
                "data" => $access_token,
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

    public function 

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

    public function create($access_token, $post)
    {
        $response = $this->facebook->post('/me/feed', $post, $access_token);
        dd($response);
    }
}
