<?php

namespace App\Routing;

use Illuminate\Routing\UrlGenerator;
use ReflectionClass;

class VersionedUrlGenerator extends UrlGenerator
{
    public static function fromGenerator(UrlGenerator $url): self
    {
        if ($url instanceof self) {
            return $url;
        }

        $source = new ReflectionClass($url);

        $versioned = new self(
            $source->getProperty('routes')->getValue($url),
            $url->getRequest(),
            $source->getProperty('assetRoot')->getValue($url)
        );

        $sessionResolver = $source->getProperty('sessionResolver')->getValue($url);
        if ($sessionResolver !== null) {
            $versioned->setSessionResolver($sessionResolver);
        }

        $keyResolver = $source->getProperty('keyResolver')->getValue($url);
        if ($keyResolver !== null) {
            $versioned->setKeyResolver($keyResolver);
        }

        $forcedRoot = $source->getProperty('forcedRoot')->getValue($url);
        if ($forcedRoot) {
            $versioned->forceRootUrl($forcedRoot);
        }

        $forceScheme = $source->getProperty('forceScheme')->getValue($url);
        if ($forceScheme) {
            $versioned->forceScheme(rtrim((string) $forceScheme, '://'));
        }

        $rootNamespace = $source->getProperty('rootNamespace')->getValue($url);
        if ($rootNamespace) {
            $versioned->setRootControllerNamespace($rootNamespace);
        }

        return $versioned;
    }

    public function asset($path, $secure = null): string
    {
        return append_asset_version(parent::asset($path, $secure), (string) $path);
    }
}
