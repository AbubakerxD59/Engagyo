<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;

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

    public static function isJpegMimeType(string $mime): bool
    {
        $mime = strtolower(trim($mime));

        return in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg'], true);
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
     * @param \GdImage|resource  $im
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
     * Convert any supported raster file to JPEG on disk.
     *
     * @return array{error: ?string, path: ?string}
     */
    private static function convertFileToJpegOnDisk(string $srcPath, Post $post): array
    {
        if (! extension_loaded('gd')) {
            return ['error' => 'PHP GD extension is required to convert images for Instagram.', 'path' => null];
        }

        $im = self::createGdImageFromFile($srcPath);
        if ($im === null) {
            return ['error' => 'Could not decode image for Instagram (unsupported or corrupt file).', 'path' => null];
        }

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
     * Ensure image is available as a public JPEG URL for Instagram (MIME not JPEG → convert + .jpg file).
     *
     * @return array{error: ?string, url: string}
     */
    public static function normalizeImageToJpegForInstagramPublish(?string $localAbsolutePath, string $publicUrl, Post $post): array
    {
        dd('1');
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

            if (self::isJpegMimeType($mime)) {
                if ($deleteTmp) {
                    @unlink($tmpPath);
                }

                return ['error' => null, 'url' => self::rewriteImagePathExtensionToJpg($publicUrl)];
            }

            $converted = self::convertFileToJpegOnDisk($workPath, $post);
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
