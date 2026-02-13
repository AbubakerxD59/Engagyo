<?php

namespace App\Services;

use App\Models\Board;
use App\Models\DomainUtmCode;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tiktok;
use Exception;
use Illuminate\Support\Facades\Log;

class UtmService
{
    /**
     * Append UTM codes to a URL if matching domain configuration exists.
     * - utm_source: always "engagyo"
     * - social_type: resolved to post's social type (facebook, pinterest, etc.) when context is provided
     * - social_profile: resolved to parent account name when context is provided
     * - All other stored values are used as-is. All values are lowercased.
     *
     * @param string $url The original URL
     * @param int $userId The user ID
     * @param \App\Models\Post|array|null $context Optional. Post model or ['social_type' => string, 'account_id' => int] for resolving social_type/social_profile
     * @return string The URL with UTM codes appended (or original URL if no match)
     */
    public static function appendUtmCodes($url, $userId, $context = null)
    {
        try {
            if (empty($url) || empty($userId)) {
                return $url;
            }

            // Extract host from URL
            $host = self::extractHost($url);
            if (!$host) {
                return $url;
            }

            // Normalize host (remove www. prefix for matching)
            $normalizedHost = self::normalizeHost($host);

            // Find matching UTM codes for this user and domain
            $utmCodes = DomainUtmCode::forUserAndDomain($userId, $normalizedHost)->get();

            if ($utmCodes->isEmpty()) {
                return $url;
            }

            // Build UTM parameters array with resolved values
            $utmParams = [];
            foreach ($utmCodes as $utmCode) {
                $value = self::resolveUtmValue($utmCode->utm_key, $utmCode->utm_value, $context);
                if ($value !== null && $value !== '') {
                    $utmParams[$utmCode->utm_key] = $value;
                }
            }

            // After all configured UTM codes, add utm_posttype from context (post type: video, link, etc.) when available
            $postType = self::getPostTypeFromContext($context);
            if ($postType !== null && $postType !== '') {
                $utmParams['utm_posttype'] = strtolower($postType);
            }

            // Append UTM codes to URL
            return self::appendQueryParams($url, $utmParams);
        } catch (Exception $e) {
            Log::error("Error appending UTM codes: " . $e->getMessage(), [
                'url' => $url,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return $url; // Return original URL on error
        }
    }

    /**
     * Resolve UTM value: utm_source => engagyo; social_type/social_profile => from context when available; else stored value. All lowercase.
     *
     * @param string $utmKey
     * @param string $storedValue
     * @param \App\Models\Post|array|null $context
     * @return string|null Value to append, or null to skip this param
     */
    private static function resolveUtmValue($utmKey, $storedValue, $context)
    {
        $storedValue = $storedValue ? trim((string) $storedValue) : '';

        // 1. For utm_source, always use "engagyo"
        if (strtolower($utmKey) === 'utm_source') {
            return 'engagyo';
        }

        // 2. For social_type / social_profile, resolve from context when available
        $storedLower = strtolower($storedValue);
        if ($storedLower === 'social_type') {
            $resolved = self::getSocialTypeFromContext($context);
            return $resolved !== null ? strtolower($resolved) : null;
        }
        if ($storedLower === 'social_profile') {
            $resolved = self::getParentAccountNameFromContext($context);
            return $resolved !== null && $resolved !== '' ? strtolower($resolved) : null;
        }

        // 3. Else use the stored value, lowercased
        return $storedValue !== '' ? strtolower($storedValue) : null;
    }

    /**
     * Get post type from context (Post or array with type). Used for utm_posttype.
     *
     * @param \App\Models\Post|array|null $context
     * @return string|null
     */
    private static function getPostTypeFromContext($context)
    {
        if ($context instanceof Post) {
            return $context->type ?? null;
        }
        if (is_array($context) && isset($context['type'])) {
            return $context['type'];
        }
        return null;
    }

    /**
     * Get social type from context (Post or array with social_type).
     *
     * @param \App\Models\Post|array|null $context
     * @return string|null
     */
    private static function getSocialTypeFromContext($context)
    {
        if ($context instanceof Post) {
            return $context->social_type ?? null;
        }
        if (is_array($context) && !empty($context['social_type'])) {
            return $context['social_type'];
        }
        return null;
    }

    /**
     * Get parent account name from context (Post or array with social_type + account_id).
     * For Facebook: Page's Facebook account username; for Pinterest: Board's Pinterest username; for TikTok: Tiktok username.
     *
     * @param \App\Models\Post|array|null $context
     * @return string|null
     */
    private static function getParentAccountNameFromContext($context)
    {
        if ($context instanceof Post) {
            $post = $context;
            if ($post->social_type === 'facebook' && $post->page) {
                $fb = $post->page->facebook;
                return $fb ? $fb->username : null;
            }
            if ($post->social_type === 'pinterest' && $post->board) {
                $pin = $post->board->pinterest;
                return $pin ? $pin->username : null;
            }
            if ($post->social_type === 'tiktok' && $post->tiktok) {
                return $post->tiktok->username ?? $post->tiktok->display_name ?? null;
            }
            return null;
        }

        if (is_array($context) && !empty($context['social_type']) && !empty($context['account_id'])) {
            $socialType = $context['social_type'];
            $accountId = $context['account_id'];
            if ($socialType === 'facebook') {
                $page = Page::find($accountId);
                $fb = $page ? $page->facebook : null;
                return $fb ? $fb->username : null;
            }
            if ($socialType === 'pinterest') {
                $board = Board::find($accountId);
                $pin = $board ? $board->pinterest : null;
                return $pin ? $pin->username : null;
            }
            if ($socialType === 'tiktok') {
                $tiktok = Tiktok::find($accountId);
                return $tiktok ? ($tiktok->username ?? $tiktok->display_name ?? null) : null;
            }
        }

        return null;
    }

    /**
     * Extract host from URL
     *
     * @param string $url
     * @return string|null
     */
    private static function extractHost($url)
    {
        $parsed = parse_url($url);
        if (isset($parsed['host'])) {
            return $parsed['host'];
        }
        // Fallback: if no host, try to extract from path
        if (isset($parsed['path'])) {
            $path = ltrim($parsed['path'], '/');
            $parts = explode('/', $path);
            return $parts[0] ?? null;
        }
        return null;
    }

    /**
     * Normalize host name (remove www. prefix for matching)
     *
     * @param string $host
     * @return string
     */
    private static function normalizeHost($host)
    {
        $host = strtolower($host);
        // Remove www. prefix if present
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        return $host;
    }

    /**
     * Append query parameters to URL, preserving existing parameters
     *
     * @param string $url
     * @param array $newParams
     * @return string
     */
    private static function appendQueryParams($url, $newParams)
    {
        $parsed = parse_url($url);

        // Parse existing query parameters
        $existingParams = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $existingParams);
        }

        // Merge new params with existing (new params override existing if key matches)
        $allParams = array_merge($existingParams, $newParams);

        // Rebuild URL
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host = isset($parsed['host']) ? $parsed['host'] : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $query = !empty($allParams) ? '?' . http_build_query($allParams) : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . $host . $port . $path . $query . $fragment;
    }
}
