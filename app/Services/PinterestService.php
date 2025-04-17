<?php

namespace App\Services;

use DirkGroenen\Pinterest\Pinterest;

class PinterestService
{
    private $pinterest;
    private $client;
    private $auth;
    private $baseUrl = "https://api.pinterest.com/v5/";
    public function __construct()
    {
        $this->pinterest = new Pinterest(env("PINTEREST_KEY"), env("PINTEREST_SECRET"));
        $this->client = new HttpService($this->baseUrl);
        $this->auth = base64_encode(env("PINTEREST_KEY") . ":" . env("PINTEREST_SECRET"));
    }

    public function getLoginUrl()
    {
        $url = $this->pinterest->auth->getLoginUrl(route("pinterest.callback"), array('boards:read', 'pins:read', 'boards:write', 'pins:write'));
        return $url;
    }

    public function getOauthToken($code = null)
    {
        $header = array(
            "Content-Type" => "application/x-www-form-urlencoded",
            "Authorization" => "Basic " . $this->auth
        );
        $this->client->withHeader($header);
        $data = array(
            "grant_type" => "authorization_code",
            "code" => (string) $code,
            "redirect_uri" => route("pinterest.callback"),
            "continuous_refresh" => false,
        );
        $oauthToken = $this->client->post("oauth/token", $data);
        dd($oauthToken);
        // $oauthToken = $this->pinterest->auth->getOAuthToken($code);
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
