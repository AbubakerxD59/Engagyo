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

        if (str_starts_with($urlOrPath, 'https://')) {
            return ['error' => null, 'url' => $urlOrPath];
        }

        if (str_starts_with($urlOrPath, 'http://')) {
            return ['error' => 'Instagram requires HTTPS media URLs.', 'url' => $urlOrPath];
        }

        if ($kind === 'video') {
            return ['error' => null, 'url' => fetchFromS3($urlOrPath)];
        }

        return ['error' => null, 'url' => url(getImage('', $urlOrPath))];
    }
}
