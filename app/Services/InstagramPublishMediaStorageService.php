<?php

namespace App\Services;

use App\Models\Post;

/**
 * Ensures media is available at a stable HTTPS URL Instagram's servers can curl.
 */
class InstagramPublishMediaStorageService
{
    /**
     * @return array{error: ?string, url: string}
     */
    public static function ensureStoredPublicUrl(string $urlOrPath, Post $post, string $kind): array
    {
        $urlOrPath = trim($urlOrPath);
        if ($urlOrPath === '') {
            return ['error' => 'Media path is empty.', 'url' => ''];
        }

        if ($kind === 'video') {
            if (str_starts_with($urlOrPath, 'https://')) {
                return ['error' => null, 'url' => $urlOrPath];
            }
            if (str_starts_with($urlOrPath, 'http://')) {
                return ['error' => 'Instagram requires HTTPS media URLs.', 'url' => $urlOrPath];
            }

            return ['error' => null, 'url' => fetchFromS3($urlOrPath)];
        }

        $localAbsolute = null;
        if (! str_starts_with($urlOrPath, 'http://') && ! str_starts_with($urlOrPath, 'https://')) {
            $safe = str_replace(['..', "\0"], '', $urlOrPath);
            $try = public_path('uploads'.DIRECTORY_SEPARATOR.$safe);
            if (is_file($try) && is_readable($try)) {
                $localAbsolute = $try;
            }
        }

        if (str_starts_with($urlOrPath, 'https://')) {
            $publicUrl = $urlOrPath;
        } elseif (str_starts_with($urlOrPath, 'http://')) {
            $publicUrl = $urlOrPath;
        } else {
            $publicUrl = url(getImage('', $urlOrPath));
        }

        return InstagramImagePrepService::normalizeImageToJpegForInstagramPublish($localAbsolute, $publicUrl, $post);
    }
}
