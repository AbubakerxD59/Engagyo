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
}
