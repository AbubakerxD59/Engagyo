<?php

namespace App\Services;

/**
 * Instagram Content Publishing: feed images must use aspect ratio between 4:5 and 1.91:1 (width/height),
 * and be readable as a normal photo. WebP/GIF or out-of-range ratios often produce Meta errors like
 * "aspect ratio () cannot be published".
 */
class InstagramImagePrepService
{
    private const MIN_RATIO = 0.8;

    private const MAX_RATIO = 1.91;

    private const MAX_EDGE = 1920;

    private const TARGET_MAX_WIDTH = 1080;

    /**
     * If the URL points at a file under public/ on this app, normalize and return a new public HTTPS image_url.
     *
     * @return array{url: string, error: ?string}
     */
    public static function normalizeResolvedHttpsImageUrl(string $url): array
    {
        $url = trim($url);
        if ($url === '' || ! preg_match('#^https://#i', $url)) {
            return ['url' => $url, 'error' => null];
        }

        $relative = self::publicRelativePathIfHostedImage($url);
        if ($relative === null) {
            return ['url' => $url, 'error' => null];
        }

        $prep = self::prepareLocalImageForInstagramFeed($relative);
        if ($prep['error'] !== null) {
            return ['url' => $url, 'error' => $prep['error']];
        }

        $out = PostService::instagramGraphImageUrlForPath($prep['relative_path']);
        if ($out === null || $out === '') {
            return ['url' => $url, 'error' => 'Could not build public URL after preparing image for Instagram.'];
        }

        return ['url' => $out, 'error' => null];
    }

    /**
     * @return ?string Relative path from public root (e.g. uploads/foo.jpg)
     */
    private static function publicRelativePathIfHostedImage(string $httpsUrl): ?string
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
        $allowed = [];
        foreach (self::publicBaseUrls() as $base) {
            $h = parse_url($base, PHP_URL_HOST);
            if (is_string($h) && $h !== '') {
                $allowed[strtolower($h)] = true;
            }
        }
        if ($urlHost !== '' && $allowed !== [] && ! isset($allowed[$urlHost])) {
            return null;
        }

        if (! is_file(public_path($rel))) {
            return null;
        }

        return $rel;
    }

    /**
     * @return list<string>
     */
    private static function publicBaseUrls(): array
    {
        $urls = array_filter([
            (string) config('services.instagram.image_public_base_url', ''),
            (string) config('app.url', ''),
        ]);

        return array_values(array_unique(array_map(fn ($u) => rtrim($u, '/'), $urls)));
    }

    /**
     * @return array{relative_path: string, error: ?string}
     */
    public static function prepareLocalImageForInstagramFeed(string $relativePath): array
    {
        $relativePath = str_replace(['..', '\\'], '', ltrim($relativePath, '/'));
        $full = public_path($relativePath);
        if (! is_file($full)) {
            return ['relative_path' => $relativePath, 'error' => 'Image not found: '.$relativePath];
        }

        $dir = dirname($relativePath);
        $base = pathinfo($relativePath, PATHINFO_FILENAME);
        $outRelative = ($dir === '.' || $dir === '') ? $base.'.igfeed.jpg' : $dir.'/'.$base.'.igfeed.jpg';
        $outFull = public_path($outRelative);

        if (is_file($outFull) && @filemtime($outFull) >= @filemtime($full)) {
            return ['relative_path' => $outRelative, 'error' => null];
        }

        $info = @getimagesize($full);
        if ($info === false) {
            return ['relative_path' => $relativePath, 'error' => 'Could not read image (unsupported or corrupt file). Use JPEG or PNG.'];
        }

        [$w, $h] = $info;
        $type = $info[2] ?? 0;
        if ($w < 1 || $h < 1) {
            return ['relative_path' => $relativePath, 'error' => 'Invalid image dimensions.'];
        }

        $ratio = $w / $h;
        $jpegOk = $type === IMAGETYPE_JPEG
            && $ratio >= self::MIN_RATIO - 0.004
            && $ratio <= self::MAX_RATIO + 0.004
            && $w >= 320
            && $h >= 320
            && $w <= self::MAX_EDGE
            && $h <= self::MAX_EDGE;

        if ($jpegOk) {
            return ['relative_path' => $relativePath, 'error' => null];
        }

        $src = self::createImageResource($full, $type);
        if ($src === false) {
            return ['relative_path' => $relativePath, 'error' => 'Could not decode image. Enable GD with JPEG, PNG'.(function_exists('imagecreatefromwebp') ? ', WebP' : '').'.'];
        }

        if ($type === IMAGETYPE_PNG) {
            $flat = self::flattenOnWhiteBackground($src);
            if ($flat !== false) {
                imagedestroy($src);
                $src = $flat;
            }
        }

        try {
            $work = self::centerCropToInstagramAspectBand($src);
            if ($work === false) {
                imagedestroy($src);

                return ['relative_path' => $relativePath, 'error' => 'Could not crop image for Instagram aspect ratio.'];
            }
            if ($work !== $src) {
                imagedestroy($src);
                $src = $work;
            }

            $nw = imagesx($src);
            $nh = imagesy($src);
            $targetW = $nw;
            if ($nw > self::TARGET_MAX_WIDTH) {
                $targetW = self::TARGET_MAX_WIDTH;
            } elseif ($nw < 320) {
                $targetW = 320;
            }
            $targetH = (int) max(1, round($nh * ($targetW / $nw)));

            $dst = imagecreatetruecolor($targetW, $targetH);
            if ($dst === false) {
                imagedestroy($src);

                return ['relative_path' => $relativePath, 'error' => 'Could not allocate image buffer.'];
            }
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $nw, $nh);
            imagedestroy($src);

            $outDir = dirname($outFull);
            if (! is_dir($outDir) && ! @mkdir($outDir, 0755, true) && ! is_dir($outDir)) {
                imagedestroy($dst);

                return ['relative_path' => $relativePath, 'error' => 'Could not create output directory.'];
            }

            if (! @imagejpeg($dst, $outFull, 90)) {
                imagedestroy($dst);

                return ['relative_path' => $relativePath, 'error' => 'Failed to write prepared JPEG.'];
            }
            imagedestroy($dst);
        } catch (\Throwable $e) {
            return ['relative_path' => $relativePath, 'error' => 'Image processing failed: '.$e->getMessage()];
        }

        return ['relative_path' => $outRelative, 'error' => null];
    }

    /**
     * Crop to the nearest valid feed aspect band (4:5 .. 1.91:1) using a center crop.
     *
     * @param  \GdImage|resource  $im
     * @return \GdImage|resource|false
     */
    private static function centerCropToInstagramAspectBand($im)
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return false;
        }
        $r = $w / $h;
        if ($r >= self::MIN_RATIO - 0.0001 && $r <= self::MAX_RATIO + 0.0001) {
            return $im;
        }

        if ($r > self::MAX_RATIO) {
            $newW = (int) max(1, round($h * self::MAX_RATIO));
            $x = (int) max(0, ($w - $newW) / 2);

            return imagecrop($im, ['x' => $x, 'y' => 0, 'width' => $newW, 'height' => $h]);
        }

        $newH = (int) max(1, round($w / self::MIN_RATIO));
        $y = (int) max(0, ($h - $newH) / 2);

        return imagecrop($im, ['x' => 0, 'y' => $y, 'width' => $w, 'height' => $newH]);
    }

    /**
     * @return \GdImage|resource|false
     */
    private static function createImageResource(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    /**
     * @param  \GdImage|resource  $im
     * @return \GdImage|resource|false
     */
    private static function flattenOnWhiteBackground($im)
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return false;
        }
        $dst = imagecreatetruecolor($w, $h);
        if ($dst === false) {
            return false;
        }
        $bg = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $bg);
        imagealphablending($dst, true);
        imagealphablending($im, true);
        imagecopy($dst, $im, 0, 0, 0, 0, $w, $h);

        return $dst;
    }
}
