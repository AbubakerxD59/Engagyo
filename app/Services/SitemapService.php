<?php

namespace App\Services;

/**
 * Class SitemapService
 *
 * A service to fetch article links from a sitemap (including sitemap indexes),
 * fetch the title for each URL, and return only valid article titles.
 */
class SitemapService
{
    /**
     * @var string The user agent to use for cURL requests.
     */
    private const USER_AGENT = 'SitemapArticleService/1.0 (PHP cURL)';

    /**
     * @var int The maximum number of attempts to fetch the sitemap/linked files.
     */
    private const MAX_RETRIES = 3;

    /**
     * @var int The initial delay in seconds before retrying a failed fetch attempt.
     */
    private const RETRY_DELAY_SECONDS = 10;

    /**
     * @var int The delay in seconds between consecutive successful requests to be polite.
     */
    private const POLITE_DELAY_SECONDS = 2;

    /**
     * @var int The maximum number of valid articles to fetch before stopping.
     */
    private const MAX_ARTICLES_LIMIT = 10;

    /**
     * Fetches articles from a given base URL's sitemap.
     *
     * @param string $baseUrl The base domain URL (e.g., https://example.com).
     * @return array An array of structured article data, where each element is
     * ['title' => string, 'link' => string]. Returns an empty array on failure.
     */
    public function fetchArticles(string $baseUrl): array
    {
        // Extract the base host for comparison
        $baseHost = $baseUrl;
        // $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        if (!$baseHost) {
            return [
                "success" => false,
                "message" => "Error: Invalid base URL provided."
            ];
        }

        $sitemapUrl = rtrim($baseUrl, '/') . '/sitemap.xml';
        try {
            // 1. Fetch the main sitemap XML content with retries
            $sitemapContent = $this->fetchContentWithRetry($sitemapUrl, self::MAX_RETRIES);

            // 2. Extract all unique article URLs (handles sitemap indexes recursively)
            $extractUrlsFromSitemap = $this->extractUrlsFromSitemap($sitemapContent);
            if ($extractUrlsFromSitemap['success']) {
                // 3. Fetch titles and filter with polite delays, stopping after MAX_ARTICLES_LIMIT
                $allUrls = $extractUrlsFromSitemap['data'];
                $validArticles = [];
                foreach ($allUrls as $url) {
                    // Stop processing and return if the limit is reached
                    if (count($validArticles) >= self::MAX_ARTICLES_LIMIT) {
                        break;
                    }
                    // IMPORTANT: Apply polite delay between article URL fetches to prevent spam detection
                    sleep(self::POLITE_DELAY_SECONDS);
                    // Pass the base host to the function for homepage filtering
                    $articleData = $this->fetchArticleTitle($url, $baseHost);
                    if ($articleData) {
                        $validArticles[] = $articleData;
                    }
                }
                $response = [
                    "success" => true,
                    "data" => $validArticles
                ];
            } else {
                $response = [
                    "success" => false,
                    "message" => $extractUrlsFromSitemap['message']
                ];
            }
        } catch (\Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return $response;
    }

    /**
     * Core function to fetch content from a URL with a retry mechanism.
     *
     * @param string $url The full URL to fetch.
     * @param int $maxRetries The maximum number of attempts.
     * @return string The fetched content.
     * @throws \Exception If fetching fails after all retries.
     */
    private function fetchContentWithRetry(string $url, int $maxRetries): string
    {
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($content !== false && $httpCode >= 200 && $httpCode < 300) {
                return $content;
            }

            if ($attempt < $maxRetries) {
                // Increase delay for subsequent retries
                $delay = self::RETRY_DELAY_SECONDS + ($attempt - 1) * 5;
                sleep($delay);
            }
        }
        throw new \Exception("Failed to fetch content from {$url} after {$maxRetries} attempts.");
    }

    /**
     * Recursively parses sitemap XML content and extracts all URLs.
     * Handles both sitemap index files and standard URL set files.
     *
     * @param string $xmlContent The raw XML content of a sitemap.
     * @return array An array of extracted URLs.
     */
    private function extractUrlsFromSitemap(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        try {
            $xml = new \SimpleXMLElement($xmlContent);
        } catch (\Exception $e) {;
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            return [
                "success" => false,
                "message" => "Failed to parse sitemap XML. " . $e->getMessage()
            ];
        }
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        $urls = [];
        // Check for sitemap index (<sitemapindex><sitemap><loc>...</loc></sitemapindex>)
        if (isset($xml->sitemap)) {
            foreach ($xml->sitemap as $sitemapEntry) {
                $linkedSitemapUrl = (string)$sitemapEntry->loc;
                // **MODIFICATION: ONLY process sitemaps containing "post-sitemap"**
                if (!str_contains($linkedSitemapUrl, 'post-sitemap')) {
                    continue; // Skip to the next entry
                }
                // Polite delay before fetching next linked sitemap
                sleep(self::POLITE_DELAY_SECONDS);
                $linkedContent = $this->fetchContentWithRetry($linkedSitemapUrl, self::MAX_RETRIES);
                // Recursively call to get URLs from the linked sitemap
                $urls = array_merge($urls, $this->extractUrlsFromSitemap($linkedContent));
            }
        }
        // Check for URL set (<urlset><url><loc>...</loc></urlset>)
        elseif (isset($xml->url)) {
            foreach ($xml->url as $urlEntry) {
                $urls[] = (string)$urlEntry->loc;
            }
        } else {
            return [
                "success" => false,
                "message" => "Warning: XML structure is neither Sitemap Index nor URL Set."
            ];
        }
        return [
            "success" => true,
            "data" => array_unique($urls)
        ];
    }

    /**
     * Fetches the HTML content for a single URL and extracts the filtered title.
     *
     * @param string $url The article URL.
     * @param string $baseHost The host of the root domain for filtering homepage links.
     * @return array|null An array containing 'title' and 'link', or null if invalid.
     */
    private function fetchArticleTitle(string $url, string $baseHost): ?array
    {
        // --- HOMEPAGE CHECK START ---
        $urlHost = parse_url($url, PHP_URL_HOST);

        // 1. Check if the URL host matches the base host
        if ($urlHost && strtolower($urlHost) === strtolower($baseHost)) {
            $urlPath = parse_url($url, PHP_URL_PATH);

            // 2. Check if the path is empty, just '/', or 'index.php/html' (common homepage indicators)
            if (
                empty($urlPath) || $urlPath === '/' ||
                preg_match('/^\/(index\.(php|html?))?$/i', $urlPath)
            ) {
                return null;
            }
        }
        // --- HOMEPAGE CHECK END ---

        // Only 1 attempt for article content, as we are already managing a polite delay
        try {
            $htmlContent = $this->fetchContentWithRetry($url, 1);
        } catch (\Exception $e) {
            return null;
        }

        if (!$htmlContent) {
            return null;
        }

        // Use regex to extract the <title> tag content (case-insensitive, single-line dot-match)
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $htmlContent, $matches)) {
            // Decode HTML entities (like &amp; or &lt;) and trim whitespace
            $title = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($this->isValidTitle($title)) {
                return [
                    'title' => $title,
                    'link' => $url
                ];
            }
        }
        return null;
    }

    /**
     * Checks if a title is valid (non-empty and not system/admin content).
     *
     * @param string $title The title to validate.
     * @return bool True if the title is valid, false otherwise.
     */
    private function isValidTitle(string $title): bool
    {
        $title = trim($title);
        if (empty($title)) {
            return false;
        }

        $titleLower = strtolower($title);

        // Keywords that typically indicate non-article, system, or administrative content
        $invalidKeywords = [
            'bot verification',
            'admin dashboard',
            'login required',
            'system message',
            'comment moderation',
            'privacy policy',
            'terms of service',
            'site map',
            'error 404',
            'page not found',
            'home page', // Common generic title
            'index',     // Common generic title
        ];

        foreach ($invalidKeywords as $keyword) {
            if (str_contains($titleLower, $keyword)) {
                return false;
            }
        }

        // Filter out very short, likely non-descriptive titles
        if (strlen($title) < 10) {
            return false;
        }

        return true;
    }
}
