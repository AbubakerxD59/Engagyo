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
     * Append UTM codes to URL when domain config exists. All values lowercased.
     *
     * @param \App\Models\Post|array|null $context Post or ['social_type', 'account_id', 'type'] for resolving social_type/social_profile/utm_posttype
     */
    public static function appendUtmCodes($url, $userId, $context = null)
    {
        try {
            if (empty($url) || empty($userId)) {
                return $url;
            }

            $host = self::extractHost($url);
            if (!$host) {
                return $url;
            }

            $normalizedHost = self::normalizeHost($host);
            $utmCodes = DomainUtmCode::forUserAndDomain($userId, $normalizedHost)->get();

            if ($utmCodes->isEmpty()) {
                return $url;
            }

            $utmParams = [];
            foreach ($utmCodes as $utmCode) {
                $value = self::resolveUtmValue($utmCode->utm_key, $utmCode->utm_value, $context);
                if ($value !== null && $value !== '') {
                    $utmParams[$utmCode->utm_key] = $value;
                }
            }

            $postType = self::getPostTypeFromContext($context);
            if ($postType !== null && $postType !== '') {
                $utmParams['utm_posttype'] = strtolower($postType);
            }

            return self::appendQueryParams($url, $utmParams);
        } catch (Exception $e) {
            Log::error("Error appending UTM codes: " . $e->getMessage(), [
                'url' => $url,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return $url;
        }
    }

    /**
     * @return string|null
     */
    private static function resolveUtmValue($utmKey, $storedValue, $context)
    {
        $storedValue = $storedValue ? trim((string) $storedValue) : '';

        if (strtolower($utmKey) === 'utm_source') {
            return 'engagyo';
        }

        $storedLower = strtolower($storedValue);
        if ($storedLower === 'social_type') {
            $resolved = self::getSocialTypeFromContext($context);
            return $resolved !== null ? strtolower($resolved) : null;
        }
        if ($storedLower === 'social_profile') {
            $resolved = self::getParentAccountNameFromContext($context);
            return $resolved !== null && $resolved !== '' ? strtolower($resolved) : null;
        }

        return $storedValue !== '' ? strtolower($storedValue) : null;
    }

    /**
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

    /** Facebook: page name; Pinterest: board's username; TikTok: username. */
    private static function getParentAccountNameFromContext($context)
    {
        if ($context instanceof Post) {
            $post = $context;
            if ($post->social_type === 'facebook' && $post->page) {
                return $post->page->name ?? null;
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
                return $page ? ($page->name ?? null) : null;
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

    /** @return string|null */
    private static function extractHost($url)
    {
        $parsed = parse_url($url);
        if (isset($parsed['host'])) {
            return $parsed['host'];
        }
        if (isset($parsed['path'])) {
            $path = ltrim($parsed['path'], '/');
            $parts = explode('/', $path);
            return $parts[0] ?? null;
        }
        return null;
    }

    private static function normalizeHost($host)
    {
        $host = strtolower($host);
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        return $host;
    }

    private static function appendQueryParams($url, $newParams)
    {
        $parsed = parse_url($url);

        $existingParams = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $existingParams);
        }

        $allParams = array_merge($existingParams, $newParams);

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host = isset($parsed['host']) ? $parsed['host'] : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $query = !empty($allParams) ? '?' . http_build_query($allParams) : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . $host . $port . $path . $query . $fragment;
    }
}
