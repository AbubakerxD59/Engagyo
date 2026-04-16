<?php

namespace App\Services;

/**
 * Instagram Content Publishing expects publicly reachable HTTPS media URLs.
 * Feed images: JPEG is the only officially supported still-image format (see Meta docs).
 *
 * @see https://developers.facebook.com/docs/instagram-platform/content-publishing/
 */
class InstagramImagePrepService
{
    /**
     * If the URL path ends with an extension other than .jpg / .jpeg, rewrite it to .jpg
     * (path only; query string and fragment are preserved).
     */
    public static function rewriteImagePathExtensionToJpg(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['path'])) {
            return $url;
        }

        $path = $parts['path'];
        $lastSlash = strrpos($path, '/');
        if ($lastSlash === false) {
            $dir = '';
            $basename = $path;
        } else {
            $dir = substr($path, 0, $lastSlash);
            $basename = substr($path, $lastSlash + 1);
        }

        if ($basename === '' || $basename === '.' || $basename === '..') {
            return $url;
        }

        $dot = strrpos($basename, '.');
        if ($dot === false) {
            return $url;
        }

        $ext = strtolower(substr($basename, $dot + 1));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            return $url;
        }

        $newBasename = substr($basename, 0, $dot).'.jpg';
        $newPath = ($dir === '' ? '' : $dir).'/'.$newBasename;

        $scheme = ($parts['scheme'] ?? 'https').'://';
        $auth = '';
        if (! empty($parts['user'])) {
            $auth = rawurlencode($parts['user']);
            if (isset($parts['pass'])) {
                $auth .= ':'.rawurlencode((string) $parts['pass']);
            }
            $auth .= '@';
        }
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        $rebuilt = $scheme.$auth.$host.$port.$newPath;
        if (! empty($parts['query'])) {
            $rebuilt .= '?'.$parts['query'];
        }
        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }

    /**
     * @return array{error: ?string, url: string}
     */
    public static function normalizeResolvedHttpsImageUrl(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['error' => 'Image URL is empty.', 'url' => ''];
        }

        if (! str_starts_with($url, 'https://')) {
            return ['error' => 'Instagram publishing requires an HTTPS image URL that Meta can fetch.', 'url' => $url];
        }

        $url = self::rewriteImagePathExtensionToJpg($url);

        return ['error' => null, 'url' => $url];
    }
}
