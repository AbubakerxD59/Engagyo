<?php

namespace App\Services;

use DirkGroenen\Pinterest\Pinterest;

class PinterestService
{
    private $pinterest;
    private $client;
    private $auth;
    private $header;
    private $baseUrl = "https://api.pinterest.com/v5/";
    public function __construct()
    {
        $pinterest_id = env("PINTEREST_KEY");
        $pinterest_secret = env("PINTEREST_SECRET");
        $this->pinterest = new Pinterest($pinterest_id, $pinterest_secret);
        $this->client = new HttpService($this->baseUrl);
        $this->auth = base64_encode("{$pinterest_id}:{$pinterest_secret}");
        $this->header = array("Content-Type" => "application/x-www-form-urlencoded", "Authorization" => "Basic " . $this->auth);
    }

    public function getLoginUrl()
    {
        $url = $this->pinterest->auth->getLoginUrl(route("pinterest.callback"), array('boards:read', 'pins:read', 'boards:write', 'pins:write'));
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

    public function me($access_token)
    {
        $me = $this->client->get("user_account", [], $this->header);
        dd($me);
        return $me;
    }
}
