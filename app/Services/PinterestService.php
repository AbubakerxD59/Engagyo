<?php

namespace App\Services;

use DirkGroenen\Pinterest\Pinterest;

class PinterestService
{
    private $pinterest;
    public function __construct()
    {
        $this->pinterest = new Pinterest(env("PINTEREST_KEY"), env("PINTEREST_SECRET"));
    }

    public function getLoginUrl()
    {
        $url = $this->pinterest->auth->getLoginUrl(route("pinterest.callback"), array('boards:read', 'pins:read', 'boards:write', 'pins:write'));
        return $url;
    }

    public function getOauthToken($code = null)
    {
        $oauthToken = $this->pinterest->auth->getOAuthToken($code);
        return $oauthToken;
    }

    public function setOAuthToken($access_token = null)
    {
        if ($access_token) {
            $this->pinterest->auth->setOAuthToken($access_token);
            return true;
        } else {
            return false;
        }
    }

    public function me()
    {
        $me = $this->pinterest->users->me();
        return $me;
    }
}
