<?php

namespace App\Services;

use Facebook\Facebook;

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
        ]);
        $this->helper = $this->facebook->getRedirectLoginHelper();
        $this->scopes = ['email', 'public_profile', 'pages_manage_metadata', 'pages_manage_posts', 'pages_read_engagement', 'pages_show_list', 'business_management', 'pages_manage_engagement', 'pages_read_user_content', 'read_insights', 'pages_manage_ads'];
    }

    public function getLoginUrl()
    {
        $url = $this->helper->getLoginUrl(route("facebook.callback"), $this->scopes);
        return $url;
    }
}
