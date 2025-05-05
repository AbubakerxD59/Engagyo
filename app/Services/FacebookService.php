<?php

namespace App\Services;

use Facebook\Facebook;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;
use App\Classes\FacebookSDK\LaravelSessionPersistentDataHandler;

class FacebookService
{
    private $facebook;
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
        $this->scopes = ['email', 'public_profile', 'pages_manage_metadata', 'pages_manage_posts', 'pages_read_engagement', 'pages_show_list', 'business_management', 'pages_manage_engagement', 'pages_read_user_content', 'read_insights', 'pages_manage_ads'];
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

    public function pages($access_token){
        $this->helper = $this->facebook->getPageTabHelper();
        $accessToken = $this->helper->getAccessToken();
        $pageId = $this->helper->getPageId();
        $pageData = $this->helper->getPageData($pageId);
        dd($accessToken, $pageId, $pageData);
        try {
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
}
