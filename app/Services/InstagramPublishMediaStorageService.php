<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Instagram Content Publishing pulls media from a public URL. This service ensures that URL
 * points at a file under this app's public web root (copying remote/S3/expiring URLs when needed).
 */
class InstagramPublishMediaStorageService
{
    private const RELATIVE_ROOT = 'uploads/instagram-publish-media';

    /** Instagram Reels can be large; cap to protect disk and memory expectations. */
    private const MAX_BYTES = 450 * 1024 * 1024;

    /**
     * @param  'image'|'video'  $kind  Used only when the URL path has no recognizable extension.
     * @return array{url: string, error: ?string, stored_relative_path?: string}
     */
    public static function ensureStoredPublicUrl(string $sourceUrl, Post $post, string $kind = 'video'): array
    {
        $sourceUrl = trim($sourceUrl);
        if ($sourceUrl === '') {
            return ['url' => '', 'error' => 'Media URL is empty.'];
        }

        if (preg_match('#^http://#i', $sourceUrl)) {
            $sourceUrl = preg_replace('#^http://#i', 'https://', $sourceUrl);
        }

        if (! preg_match('#^https://#i', $sourceUrl)) {
            return ['url' => '', 'error' => 'Instagram publishing requires an HTTPS media URL.'];
        }

        $rel = self::publicRelativePathIfOurHostedUrl($sourceUrl);
        if ($rel !== null && is_file(public_path($rel))) {
            $url = PostService::instagramGraphImageUrlForPath($rel);
            if ($url !== null && $url !== '') {
                return ['url' => $url, 'error' => null, 'stored_relative_path' => $rel];
            }
        }

        $postId = (int) $post->getKey();
        if ($postId < 1) {
            return ['url' => '', 'error' => 'Invalid post id for Instagram media staging.'];
        }

        $dir = self::RELATIVE_ROOT.'/'.$postId;
        $fullDir = public_path($dir);
        if (! is_dir($fullDir) && ! @mkdir($fullDir, 0755, true) && ! is_dir($fullDir)) {
            return ['url' => '', 'error' => 'Could not create directory for Instagram media.'];
        }

        $ext = self::guessExtensionFromUrl($sourceUrl, $kind);
        $relative = $dir.'/'.Str::uuid()->toString().'.'.$ext;
        $dest = public_path($relative);

        $response = Http::timeout(600)
            ->withOptions([
                'sink' => $dest,
                'connect_timeout' => 30,
                'allow_redirects' => true,
            ])
            ->get($sourceUrl);

        if (! $response->successful()) {
            @unlink($dest);

            return ['url' => '', 'error' => 'Could not download media for Instagram (HTTP '.$response->status().').'];
        }

        if (! is_file($dest)) {
            return ['url' => '', 'error' => 'Instagram media download did not create a file.'];
        }

        $size = @filesize($dest);
        if ($size === false || $size < 1) {
            @unlink($dest);

            return ['url' => '', 'error' => 'Downloaded media file was empty.'];
        }
        if ($size > self::MAX_BYTES) {
            @unlink($dest);

            return ['url' => '', 'error' => 'Media exceeds maximum size allowed for Instagram staging ('.round(self::MAX_BYTES / 1048576).' MB).'];
        }

        $url = PostService::instagramGraphImageUrlForPath($relative);
        if ($url === null || $url === '') {
            @unlink($dest);

            return ['url' => '', 'error' => 'Could not build public URL for stored Instagram media. Set APP_URL or INSTAGRAM_IMAGE_PUBLIC_BASE_URL.'];
        }

        return ['url' => $url, 'error' => null, 'stored_relative_path' => $relative];
    }

    /**
     * If this HTTPS URL is served from our configured public origin, return the path relative to public/.
     */
    private static function publicRelativePathIfOurHostedUrl(string $httpsUrl): ?string
    {
        $path = parse_url($httpsUrl, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }
        $path = str_replace('\\', '/', rawurldecode($path));
        $rel = ltrim($path, '/');
        if ($rel === '' || str_contains($rel, '..')) {
            return null;
        }

        $urlHost = strtolower((string) parse_url($httpsUrl, PHP_URL_HOST));
        if ($urlHost === '') {
            return null;
        }

        $allowed = [];
        foreach ([config('services.instagram.image_public_base_url'), config('app.url')] as $base) {
            $base = rtrim((string) $base, '/');
            if ($base === '') {
                continue;
            }
            $h = parse_url($base, PHP_URL_HOST);
            if (is_string($h) && $h !== '') {
                $allowed[strtolower($h)] = true;
            }
        }

        if ($allowed === [] || ! isset($allowed[$urlHost])) {
            return null;
        }

        return $rel;
    }

    /**
     * @param  'image'|'video'  $kind
     */
    private static function guessExtensionFromUrl(string $url, string $kind): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo(is_string($path) ? $path : '', PATHINFO_EXTENSION));
        if ($ext !== '' && preg_match('/^[a-z0-9]{1,10}$/', $ext)) {
            return $ext;
        }

        return $kind === 'image' ? 'jpg' : 'mp4';
    }
}
