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

        $lower = strtolower($url);
        if (str_contains($lower, '.webp')) {
            return ['error' => 'Instagram feed images should be JPEG. Please upload a .jpg / .jpeg file.', 'url' => $url];
        }

        return ['error' => null, 'url' => $url];
    }
}
