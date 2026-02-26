<?php

namespace App\Services;

use App\Models\ShortLink;
use App\Models\User;

class UrlShortenerService
{
    public function shortenForUser(User $user, string $originalUrl): array
    {
        $normalizedUrl = ShortLink::normalizeUrl($originalUrl);
        if ($normalizedUrl === '') {
            return ['success' => false, 'message' => 'Invalid URL'];
        }

        $parsed = parse_url($normalizedUrl);
        $path = $parsed['path'] ?? '';
        if (preg_match('#^/s/[a-zA-Z0-9]+$#', $path)) {
            return ['success' => true, 'short_url' => $normalizedUrl, 'short_code' => '', 'original_url' => $normalizedUrl];
        }

        $existing = ShortLink::where('user_id', $user->id)
            ->where('original_url', $normalizedUrl)
            ->first();

        if ($existing) {
            return [
                'success' => true,
                'short_url' => url('/s/' . $existing->short_code),
                'short_code' => $existing->short_code,
                'original_url' => $existing->original_url,
            ];
        }

        $shortCode = ShortLink::generateUniqueCode(6);
        ShortLink::create([
            'user_id' => $user->id,
            'short_code' => $shortCode,
            'original_url' => $normalizedUrl,
        ]);

        return [
            'success' => true,
            'short_url' => url('/s/' . $shortCode),
            'short_code' => $shortCode,
            'original_url' => $normalizedUrl,
        ];
    }

    public function shortenUrlsInText(User $user, string $text, bool $shouldShorten): string
    {
        if (!$shouldShorten || empty(trim($text))) {
            return $text;
        }

        $result = preg_replace_callback('#https?://[^\s"\'<>]+#', function ($matches) use ($user) {
            $originalUrl = rtrim(preg_replace('/[.,;:!?)]+$/', '', $matches[0]));
            $shortened = $this->shortenForUser($user, $originalUrl);
            return $shortened['success'] ? $shortened['short_url'] : $originalUrl;
        }, $text);

        return $result ?? $text;
    }
}
