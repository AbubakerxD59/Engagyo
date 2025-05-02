<?php

namespace App\Classes\FacebookSDK;

use Facebook\PersistentData\PersistentDataInterface;

class LaravelSessionPersistentDataHandler implements PersistentDataInterface
{
    public function get($key)
    {
        return session()->get($key);
    }

    public function set($key, $value)
    {
        session()->put($key, $value);
    }
}
