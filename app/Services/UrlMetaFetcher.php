<?php

namespace App\Services;

use Exception;

/**
 * Class UrlMetadataFetcher
 * * A service class to fetch the title and open graph thumbnail image from a given URL,
 * implementing a retry mechanism with a delay for transient errors.
 */
class UrlMetaFetcher
{
    // Maximum number of attempts allowed, including the initial one
    private const MAX_RETRIES = 3;

    // Delay in seconds before each retry
    private const RETRY_DELAY_SECONDS = 5;

    /**
     * Fetches metadata (title and thumbnail) from a URL with retry logic.
     *
     * @param string $url The URL to fetch metadata from.
     * @return array|null An array containing 'title' and 'thumbnail' on success, 
     * or null if all attempts fail.
     */
    public function fetchMetadata(string $url): ?array
    {
        // 1. Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo "Error: Invalid URL provided.\n";
            return null;
        }

        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {
            $attempts++;
            echo "--- Attempt {$attempts} to fetch metadata from: {$url} ---\n";

            try {
                // 2. Fetch the content
                $html = $this->fetchUrlContent($url);

                // 3. Extract metadata
                $metadata = $this->extractMetadata($html);

                // If we successfully got data, return it immediately
                if ($metadata['title'] || $metadata['thumbnail']) {
                    echo "Success! Metadata fetched on attempt {$attempts}.\n";
                    return $metadata;
                }

                // If fetching succeeded but no metadata was found, we still might retry if content was empty/malformed
                throw new Exception("Content fetched, but no relevant metadata (title/og:image) found.");
            } catch (Exception $e) {
                echo "Attempt {$attempts} failed: " . $e->getMessage() . "\n";

                // If this was the last attempt, log final failure and return null
                if ($attempts === self::MAX_RETRIES) {
                    echo "All " . self::MAX_RETRIES . " attempts failed.\n";
                    return null;
                }

                // Delay before the next retry
                echo "Waiting " . self::RETRY_DELAY_SECONDS . " seconds before retrying...\n";
                sleep(self::RETRY_DELAY_SECONDS);
            }
        }

        return null;
    }

    /**
     * Uses cURL to fetch the content of the given URL.
     *
     * @param string $url The URL to fetch.
     * @return string The fetched HTML content.
     * @throws Exception If cURL fails or returns a non-200 status code.
     */
    private function fetchUrlContent(string $url): string
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set a reasonable timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // Follow redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // User agent to avoid generic scraping blocks
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($html === false) {
            throw new Exception("cURL error: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP request failed with status code: " . $httpCode);
        }

        return $html;
    }

    /**
     * Extracts the page title and Open Graph image URL using simple regex.
     *
     * @param string $html The HTML content to parse.
     * @return array Associative array with 'title' and 'thumbnail'.
     */
    private function extractMetadata(string $html): array
    {
        $title = '';
        $thumbnail = '';

        // Extract <title> content
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
            // Trim whitespace and remove HTML entities
            $title = trim(strip_tags(html_entity_decode($matches[1])));
        }

        // Extract Open Graph Image (og:image)
        if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\'](.*?)["\']/is', $html, $matches)) {
            $thumbnail = trim($matches[1]);
        }

        // Fallback for non-OG image (e.g., twitter:image, although og:image is standard)
        if (empty($thumbnail) && preg_match('/<meta\s+name=["\']twitter:image["\']\s+content=["\'](.*?)["\']/is', $html, $matches)) {
            $thumbnail = trim($matches[1]);
        }


        return [
            'title' => $title,
            'thumbnail' => $thumbnail,
        ];
    }
}
