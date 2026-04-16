<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;

/**
 * Instagram Content Publishing expects publicly reachable HTTPS media URLs.
 * Feed images: JPEG; aspect ratio must be within Meta limits (roughly 4:5 … 1.91:1).
 * Story images: normalized toward 9:16 when possible.
 *
 * @see https://developers.facebook.com/docs/instagram-platform/content-publishing/
 */
class InstagramImagePrepService
{
    /** Feed photo: min width/height ratio (portrait 4:5). */
    private const FEED_ASPECT_MIN = 0.8;

    /** Feed photo: max width/height ratio (landscape 1.91:1). */
    private const FEED_ASPECT_MAX = 1.91;

    /** Story image target width / height. */
    private const STORY_ASPECT = 0.5625; // 9:16

    private const MIN_SHORT_EDGE = 320;

    private const FEED_MAX_LONG_EDGE = 2160;

    private const STORY_MAX_LONG_EDGE = 1920;

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

    public static function isJpegMimeType(string $mime): bool
    {
        $mime = strtolower(trim($mime));

        return in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg'], true);
    }

    private static function useStoryAspectRules(Post $post): bool
    {
        return (string) $post->type === 'story';
    }

    /**
     * True when raster already meets Instagram rules (no GD pipeline needed).
     */
    private static function rasterMeetsInstagramRules(int $w, int $h, bool $forStory, string $mime): bool
    {
        if ($w < 1 || $h < 1) {
            return false;
        }
        if (! self::isJpegMimeType($mime)) {
            return false;
        }

        $r = $w / $h;
        $short = min($w, $h);
        $long = max($w, $h);

        if ($forStory) {
            if (abs($r - self::STORY_ASPECT) > 0.02) {
                return false;
            }
            if ($short < self::MIN_SHORT_EDGE || $long > self::STORY_MAX_LONG_EDGE) {
                return false;
            }

            return true;
        }

        if ($r < self::FEED_ASPECT_MIN - 1e-4 || $r > self::FEED_ASPECT_MAX + 1e-4) {
            return false;
        }
        if ($short < self::MIN_SHORT_EDGE || $long > self::FEED_MAX_LONG_EDGE) {
            return false;
        }

        return true;
    }

    /**
     * Center-crop to satisfy aspect ratio rules (feed: band4:5…1.91:1; story: 9:16).
     *
     * @param \GdImage|resource $im
     * @return \GdImage|resource
     */
    private static function applyInstagramAspectCenterCrop($im, bool $forStory)
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return $im;
        }

        $r = $w / $h;
        if ($forStory) {
            $minR = self::STORY_ASPECT;
            $maxR = self::STORY_ASPECT;
        } else {
            $minR = self::FEED_ASPECT_MIN;
            $maxR = self::FEED_ASPECT_MAX;
        }

        $srcX = 0;
        $srcY = 0;
        $cropW = $w;
        $cropH = $h;

        if ($r > $maxR + 1e-6) {
            $cropW = max(1, (int) round($h * $maxR));
            $cropH = $h;
            $srcX = (int) (($w - $cropW) / 2);
            $srcY = 0;
        } elseif ($r < $minR - 1e-6) {
            $cropW = $w;
            $cropH = max(1, (int) round($w / $minR));
            $srcX = 0;
            $srcY = (int) (($h - $cropH) / 2);
        }

        if ($cropW === $w && $cropH === $h) {
            return $im;
        }

        $dest = imagecreatetruecolor($cropW, $cropH);
        if ($dest === false) {
            return $im;
        }

        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);
        imagecopy($dest, $im, 0, 0, $srcX, $srcY, $cropW, $cropH);
        imagedestroy($im);

        return $dest;
    }

    /**
     * Scale so short edge ≥ MIN_SHORT_EDGE and long edge ≤ max for feed/story.
     *
     * @param \GdImage|resource $im
     * @return \GdImage|resource
     */
    private static function scaleInstagramRasterBounds($im, bool $forStory)
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return $im;
        }

        $short = min($w, $h);
        $long = max($w, $h);
        $maxLong = $forStory ? self::STORY_MAX_LONG_EDGE : self::FEED_MAX_LONG_EDGE;

        $scale = 1.0;
        if ($short < self::MIN_SHORT_EDGE) {
            $scale = self::MIN_SHORT_EDGE / $short;
        }
        if ($long * $scale > $maxLong) {
            $scale = min($scale, $maxLong / $long);
        }

        if ($scale >= 0.999 && $scale <= 1.001) {
            return $im;
        }

        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $scaled = imagescale($im, $nw, $nh, IMG_BILINEAR_FIXED);
        if ($scaled === false) {
            return $im;
        }
        imagedestroy($im);

        return $scaled;
    }

    /**
     * Decode raster image bytes to a truecolor GD image (JPEG, PNG, GIF, WebP when supported).
     *
     * @return \GdImage|resource|null
     */
    private static function createGdImageFromFile(string $absolutePath)
    {
        if (! is_readable($absolutePath)) {
            return null;
        }

        $data = @file_get_contents($absolutePath);
        if ($data === false || $data === '') {
            return null;
        }

        $im = @imagecreatefromstring($data);
        if ($im === false) {
            return null;
        }

        if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($im)) {
            imagepalettetotruecolor($im);
        }

        return $im;
    }

    /**
     * Flatten alpha onto white and write JPEG.
     *
     * @param \GdImage|resource $im
     */
    private static function writeTruecolorImageAsJpeg($im, string $destPath, int $quality = 90): bool
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return false;
        }

        $out = imagecreatetruecolor($w, $h);
        if ($out === false) {
            return false;
        }

        $white = imagecolorallocate($out, 255, 255, 255);
        imagefill($out, 0, 0, $white);
        imagealphablending($out, true);
        imagecopy($out, $im, 0, 0, 0, 0, $w, $h);

        $ok = imagejpeg($out, $destPath, $quality);
        imagedestroy($out);

        return $ok;
    }

    /**
     * Decode → aspect crop → scale → write JPEG under public/uploads/instagram_jpeg.
     *
     * @return array{error: ?string, path: ?string}
     */
    private static function processImageFileToInstagramJpeg(string $srcPath, Post $post): array
    {
        if (! extension_loaded('gd')) {
            return ['error' => 'PHP GD extension is required to prepare images for Instagram.', 'path' => null];
        }

        $im = self::createGdImageFromFile($srcPath);
        if ($im === null) {
            return ['error' => 'Could not decode image for Instagram (unsupported or corrupt file).', 'path' => null];
        }

        $forStory = self::useStoryAspectRules($post);
        $im = self::applyInstagramAspectCenterCrop($im, $forStory);
        $im = self::scaleInstagramRasterBounds($im, $forStory);

        $dir = public_path('uploads/instagram_jpeg');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            imagedestroy($im);

            return ['error' => 'Could not create directory for Instagram JPEG output.', 'path' => null];
        }

        $fileName = 'ig_'.$post->id.'_'.bin2hex(random_bytes(8)).'.jpg';
        $destPath = $dir.DIRECTORY_SEPARATOR.$fileName;

        if (! self::writeTruecolorImageAsJpeg($im, $destPath, 90)) {
            imagedestroy($im);

            return ['error' => 'Failed to write JPEG file for Instagram.', 'path' => null];
        }

        imagedestroy($im);

        return ['error' => null, 'path' => $destPath];
    }

    /**
     * Ensure image is available as a public JPEG URL for Instagram (format + aspect + size).
     *
     * @return array{error: ?string, url: string}
     */
    public static function normalizeImageToJpegForInstagramPublish(?string $localAbsolutePath, string $publicUrl, Post $post): array
    {
        $publicUrl = trim($publicUrl);
        if ($publicUrl === '') {
            return ['error' => 'Image URL is empty.', 'url' => ''];
        }

        $tmpPath = null;
        $deleteTmp = false;

        try {
            if ($localAbsolutePath !== null && $localAbsolutePath !== '' && is_readable($localAbsolutePath)) {
                $workPath = $localAbsolutePath;
            } else {
                $resp = Http::timeout(120)
                    ->withOptions(['allow_redirects' => true])
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; Engagyo/1.0)',
                        'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    ])
                    ->get($publicUrl);

                if (! $resp->successful()) {
                    return ['error' => 'Could not download image for Instagram publishing.', 'url' => ''];
                }

                $tmpPath = tempnam(sys_get_temp_dir(), 'igpub');
                if ($tmpPath === false) {
                    return ['error' => 'Could not create temp file for image.', 'url' => ''];
                }
                file_put_contents($tmpPath, $resp->body());
                $workPath = $tmpPath;
                $deleteTmp = true;
            }

            $mime = @mime_content_type($workPath) ?: '';
            if ($mime === '' || ! str_starts_with(strtolower($mime), 'image/')) {
                return ['error' => 'URL does not point to an image ('.$mime.').', 'url' => ''];
            }

            $info = @getimagesize($workPath);
            $forStory = self::useStoryAspectRules($post);
            if ($info !== false
                && self::rasterMeetsInstagramRules((int) $info[0], (int) $info[1], $forStory, $mime)) {
                if ($deleteTmp) {
                    @unlink($tmpPath);
                }

                return ['error' => null, 'url' => self::rewriteImagePathExtensionToJpg($publicUrl)];
            }

            $converted = self::processImageFileToInstagramJpeg($workPath, $post);
            if ($converted['error'] !== null) {
                return ['error' => $converted['error'], 'url' => ''];
            }

            $relative = 'instagram_jpeg/'.basename((string) $converted['path']);

            return ['error' => null, 'url' => url(asset('uploads/'.$relative))];
        } finally {
            if ($deleteTmp && $tmpPath !== null && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
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
