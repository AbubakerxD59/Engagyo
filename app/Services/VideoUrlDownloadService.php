<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Download remote video/image bytes from a public URL (same behaviour as Jogg AI media fetch):
 * Google Drive share links are rewritten to export URLs; response Content-Type is validated.
 */
class VideoUrlDownloadService
{
    /**
     * Google Drive share/preview URLs return HTML. Rewrite so Http client can follow redirects to raw bytes.
     */
    public static function resolveGoogleDriveDirectUrl(string $url): string
    {
        if (preg_match('#^https?://(?:www\.)?drive\.google\.com/file/(?:u/\d+/)?d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        if (preg_match('#^https?://(?:www\.)?drive\.google\.com/open#', $url)
            && preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        return $url;
    }

    /**
     * Download URL body when Content-Type is one of $allowedContentTypes.
     *
     * @param  list<string>  $allowedContentTypes  lowercase MIME types (e.g. video/mp4)
     * @return array{body: string, extension: string, content_type: string}|null
     */
    public static function fetchBinary(string $url, array $allowedContentTypes, int $timeoutSeconds = 120): ?array
    {
        $originalUrl = $url;
        $fetchUrl = self::resolveGoogleDriveDirectUrl($url);

        try {
            $response = Http::timeout($timeoutSeconds)->get($fetchUrl);

            if (!$response->successful()) {
                Log::warning('VideoUrlDownloadService: HTTP not successful', [
                    'url' => $originalUrl,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));

            if ($fetchUrl !== $originalUrl && str_starts_with($contentType, 'text/html')) {
                Log::warning('VideoUrlDownloadService: Drive returned HTML instead of file', ['url' => $originalUrl]);

                return null;
            }

            if (! in_array($contentType, $allowedContentTypes, true)) {
                Log::warning('VideoUrlDownloadService: rejected content type', [
                    'url' => $originalUrl,
                    'content_type' => $contentType,
                ]);

                return null;
            }

            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'video/mp4' => 'mp4',
                'video/quicktime' => 'mov',
                'video/x-msvideo' => 'avi',
                'video/x-matroska' => 'mkv',
                'video/webm' => 'webm',
            ];
            $extension = $extensionMap[$contentType] ?? 'bin';

            return [
                'body' => $response->body(),
                'extension' => $extension,
                'content_type' => $contentType,
            ];
        } catch (\Throwable $e) {
            Log::error('VideoUrlDownloadService: ' . $e->getMessage(), ['url' => $originalUrl]);

            return null;
        }
    }

    /**
     * Allowed types for Jogg AI (image + video).
     *
     * @return list<string>
     */
    public static function joggAllowedContentTypes(): array
    {
        return [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm',
        ];
    }

    /**
     * Allowed types for API video posts.
     *
     * @return list<string>
     */
    public static function apiVideoAllowedContentTypes(): array
    {
        return [
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm',
        ];
    }

    /**
     * @return array{body: string, extension: string, content_type: string}|null
     */
    public static function downloadVideoForApi(string $videoUrl): ?array
    {
        return self::fetchBinary($videoUrl, self::apiVideoAllowedContentTypes(), 300);
    }
}
