<?php

namespace App\Services;

use App\Models\ShortLink;
use App\Models\User;

class UrlShortenerService
{
    public function __construct(
        protected ShrtLnkApiService $shrtLnkApi
    ) {}

    public function shortenForUser(User $user, string $originalUrl, bool $urlCloak = true): array
    {
        return $this->createShortLink(
            $user->id,
            $originalUrl,
            null,
            null,
            $urlCloak
        );
    }

    /**
     * Create or return an existing short link for a user or anonymous visitor.
     *
     * @return array{
     *     success: bool,
     *     message?: string,
     *     short_url?: string,
     *     short_code?: string,
     *     original_url?: string,
     *     existing?: bool,
     *     id?: int,
     *     clicks?: int,
     * }
     */
    public function createShortLink(
        ?int $userId,
        string $originalUrl,
        ?string $userAgent = null,
        ?string $ipAddress = null,
        bool $urlCloak = true
    ): array {
        $normalizedUrl = ShortLink::normalizeUrl($originalUrl);
        if ($normalizedUrl === '') {
            return ['success' => false, 'message' => 'Invalid URL'];
        }

        if ($this->isAlreadyShortUrl($normalizedUrl)) {
            return [
                'success' => true,
                'short_url' => $normalizedUrl,
                'short_code' => '',
                'original_url' => $normalizedUrl,
                'existing' => true,
            ];
        }

        $existing = $this->findExisting($normalizedUrl, $userId, $userAgent, $urlCloak);
        if ($existing) {
            return $this->formatLinkResponse($existing, true);
        }

        if ($this->shrtLnkApi->isEnabled()) {
            $apiResult = $this->createViaShrtLnk($normalizedUrl, $userId, $userAgent, $ipAddress, $urlCloak);
            if ($apiResult['success']) {
                return $apiResult;
            }

            if (! config('shrtlnk.fallback_local', false)) {
                return $apiResult;
            }
        }

        return $this->createLocalShortLink($normalizedUrl, $userId, $userAgent, $ipAddress, $urlCloak);
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function updateShortLink(ShortLink $shortLink, string $originalUrl, bool $urlCloak): array
    {
        $normalizedUrl = ShortLink::normalizeUrl($originalUrl);
        if ($normalizedUrl === '') {
            return ['success' => false, 'message' => 'Invalid URL'];
        }

        if ($this->shrtLnkApi->isEnabled() && $shortLink->short_code !== '') {
            $api = $this->shrtLnkApi->updateLink($shortLink->short_code, [
                'original_url' => $normalizedUrl,
                'url_cloak' => $urlCloak ? 1 : 0,
                'source' => config('shrtlnk.source', 'engagyo'),
                'user_id' => $shortLink->user_id,
            ]);

            if (! $api['success']) {
                return [
                    'success' => false,
                    'message' => $api['message'] ?? 'Failed to update short link.',
                ];
            }

            $data = $api['data'] ?? [];
            $shortLink->update([
                'original_url' => (string) ($data['original_url'] ?? $normalizedUrl),
                'url_cloak' => (bool) ($data['url_cloak'] ?? $data['cloaked'] ?? $urlCloak),
                'short_url' => ! empty($data['short_url']) ? (string) $data['short_url'] : $shortLink->short_url,
            ]);

            return ['success' => true];
        }

        $shortLink->update([
            'original_url' => $normalizedUrl,
            'url_cloak' => $urlCloak,
        ]);

        return ['success' => true];
    }

    public function shortenUrlsInText(User $user, string $text, bool $shouldShorten): string
    {
        if (! $shouldShorten || empty(trim($text))) {
            return $text;
        }

        $result = preg_replace_callback('#https?://[^\s"\'<>]+#', function ($matches) use ($user) {
            $originalUrl = rtrim(preg_replace('/[.,;:!?)]+$/', '', $matches[0]));
            $shortened = $this->shortenForUser($user, $originalUrl);

            return $shortened['success'] ? ($shortened['short_url'] ?? $originalUrl) : $originalUrl;
        }, $text);

        return $result ?? $text;
    }

    protected function findExisting(string $normalizedUrl, ?int $userId, ?string $userAgent, bool $urlCloak): ?ShortLink
    {
        if ($userId !== null) {
            return ShortLink::where('user_id', $userId)
                ->where('original_url', $normalizedUrl)
                ->where('url_cloak', $urlCloak)
                ->first();
        }

        if ($userAgent !== null && $userAgent !== '') {
            return ShortLink::whereNull('user_id')
                ->where('user_agent', $userAgent)
                ->where('original_url', $normalizedUrl)
                ->where('url_cloak', $urlCloak)
                ->first();
        }

        return ShortLink::whereNull('user_id')
            ->whereNull('user_agent')
            ->where('original_url', $normalizedUrl)
            ->where('url_cloak', $urlCloak)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function createViaShrtLnk(
        string $normalizedUrl,
        ?int $userId,
        ?string $userAgent,
        ?string $ipAddress,
        bool $urlCloak
    ): array {
        $api = $this->shrtLnkApi->createLink([
            'original_url' => $normalizedUrl,
            'user_id' => $userId,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'url_cloak' => $urlCloak ? 1 : 0,
        ]);

        if (! $api['success'] || empty($api['data'])) {
            return [
                'success' => false,
                'message' => $api['message'] ?? 'Failed to create short link.',
            ];
        }

        $data = $api['data'];
        $shortCode = (string) ($data['short_code'] ?? '');
        $shortUrl = (string) ($data['short_url'] ?? '');

        if ($shortCode === '' || $shortUrl === '') {
            return [
                'success' => false,
                'message' => 'ShrtLnk did not return a short link.',
            ];
        }

        $shortLink = ShortLink::create([
            'user_id' => $userId,
            'short_code' => $shortCode,
            'shrtlnk_id' => isset($data['id']) ? (int) $data['id'] : null,
            'short_url' => $shortUrl,
            'original_url' => (string) ($data['original_url'] ?? $normalizedUrl),
            'url_cloak' => (bool) ($data['url_cloak'] ?? $data['cloaked'] ?? $urlCloak),
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'clicks' => (int) ($data['clicks'] ?? 0),
        ]);

        return $this->formatLinkResponse($shortLink, (bool) ($data['existing'] ?? false));
    }

    /**
     * @return array<string, mixed>
     */
    protected function createLocalShortLink(
        string $normalizedUrl,
        ?int $userId,
        ?string $userAgent,
        ?string $ipAddress,
        bool $urlCloak
    ): array {
        $shortCode = ShortLink::generateUniqueCode(6);
        $shortUrl = url('/s/'.$shortCode);

        $shortLink = ShortLink::create([
            'user_id' => $userId,
            'short_code' => $shortCode,
            'short_url' => $shortUrl,
            'original_url' => $normalizedUrl,
            'url_cloak' => $urlCloak,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);

        return $this->formatLinkResponse($shortLink, false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatLinkResponse(ShortLink $shortLink, bool $existing): array
    {
        return [
            'success' => true,
            'id' => $shortLink->id,
            'short_url' => $shortLink->publicShortUrl(),
            'short_code' => $shortLink->short_code,
            'original_url' => $shortLink->original_url,
            'url_cloak' => (bool) $shortLink->url_cloak,
            'clicks' => $shortLink->clicks,
            'existing' => $existing,
            'created_at' => $shortLink->created_at?->toIso8601String(),
        ];
    }

    protected function isAlreadyShortUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');
        $path = $parsed['path'] ?? '';

        if (! preg_match('#^/s/[a-zA-Z0-9]+$#', $path)) {
            return false;
        }

        $shrtlnkHost = strtolower((string) parse_url(config('shrtlnk.base_url'), PHP_URL_HOST));
        if ($shrtlnkHost !== '' && $host === $shrtlnkHost) {
            return true;
        }

        $appHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));

        return $appHost !== '' && $host === $appHost;
    }
}
