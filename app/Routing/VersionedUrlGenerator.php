<?php

namespace App\Routing;

use Illuminate\Routing\UrlGenerator;

class VersionedUrlGenerator
{
    public function __construct(private UrlGenerator $url) {}

    public function asset($path, $secure = null): string
    {
        return append_asset_version($this->url->asset($path, $secure), (string) $path);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->url->{$method}(...$parameters);
    }
}
