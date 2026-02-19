<?php

namespace App\Services;

use App\Models\ShortLink;
use App\Models\User;

class UrlShortenerService
{
    /**
     * Shorten a URL for a user. Returns existing short link if the URL was already shortened by this user.
     *
     * @param User $user
     * @param string $originalUrl
     * @return array{success: bool, short_url?: string, short_code?: string, original_url?: string, message?: string}
     */
    public function shortenForUser(User $user, string $originalUrl): array
    {
        $normalizedUrl = ShortLink::normalizeUrl($originalUrl);
        if ($normalizedUrl === '') {
            return ['success' => false, 'message' => 'Invalid URL'];
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

    /**
     * Extract the first URL from text (matches http/https URLs).
     */
    public static function extractFirstUrlFromText(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        if (preg_match('#https?://[^\s"\'<>]+#', $text, $m)) {
            return rtrim(preg_replace('/[.,;:!?)]+$/', '', $m[0]));
        }
        return null;
    }

    /**
     * Replace all URLs in text with their shortened versions.
     * Only replaces URLs that look like http/https links.
     *
     * @param User $user
     * @param string $text
     * @param bool $shouldShorten Whether to actually shorten (e.g. when account has url_shortener_enabled)
     * @return string
     */
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
