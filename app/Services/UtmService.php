<?php

namespace App\Services;

use App\Models\DomainUtmCode;
use Exception;
use Illuminate\Support\Facades\Log;

class UtmService
{
    /**
     * Append UTM codes to a URL if matching domain configuration exists
     *
     * @param string $url The original URL
     * @param int $userId The user ID
     * @return string The URL with UTM codes appended (or original URL if no match)
     */
    public static function appendUtmCodes($url, $userId)
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

            // Build UTM parameters array
            $utmParams = [];
            foreach ($utmCodes as $utmCode) {
                $utmParams[$utmCode->utm_key] = $utmCode->utm_value;
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
