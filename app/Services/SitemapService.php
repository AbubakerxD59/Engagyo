<?php

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
        $sitemapUrl = rtrim($baseUrl, '/') . '/sitemap.xml';
        echo "Starting sitemap processing from: " . $sitemapUrl . "\n";

        try {
            // 1. Fetch the main sitemap XML content with retries
            $sitemapContent = $this->fetchContentWithRetry($sitemapUrl, self::MAX_RETRIES);

            // 2. Extract all unique article URLs (handles sitemap indexes recursively)
            $allUrls = $this->extractUrlsFromSitemap($sitemapContent);

            // 3. Fetch titles and filter with polite delays, stopping after MAX_ARTICLES_LIMIT
            $validArticles = [];

            foreach ($allUrls as $url) {
                // Stop processing and return if the limit is reached
                if (count($validArticles) >= self::MAX_ARTICLES_LIMIT) {
                    echo "Stopping process. Reached maximum limit of " . self::MAX_ARTICLES_LIMIT . " valid articles.\n";
                    break;
                }

                // IMPORTANT: Apply polite delay between article URL fetches to prevent spam detection
                sleep(self::POLITE_DELAY_SECONDS);

                $articleData = $this->fetchArticleTitle($url);

                if ($articleData) {
                    $validArticles[] = $articleData;
                }
            }

            echo "Finished processing. Found " . count($validArticles) . " valid articles.\n";
            return $validArticles;
        } catch (\Exception $e) {
            echo "Fatal Error: " . $e->getMessage() . "\n";
            return []; // Return empty array on final failure
        }
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
                echo " -> Fetch successful on attempt {$attempt}.\n";
                return $content;
            }

            echo " -> Fetch failed on attempt {$attempt}: HTTP Code {$httpCode}, cURL Error: {$curlError}\n";

            if ($attempt < $maxRetries) {
                // Increase delay for subsequent retries
                $delay = self::RETRY_DELAY_SECONDS + ($attempt - 1) * 5;
                echo " -> Retrying in {$delay} seconds...\n";
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
        } catch (\Exception $e) {
            echo "Warning: Failed to parse sitemap XML. " . $e->getMessage() . "\n";
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            return [];
        }
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $urls = [];

        // Check for sitemap index (<sitemapindex><sitemap><loc>...</loc></sitemapindex>)
        if (isset($xml->sitemap)) {
            echo "Found sitemap index. Processing linked sitemaps...\n";
            foreach ($xml->sitemap as $sitemapEntry) {
                $linkedSitemapUrl = (string)$sitemapEntry->loc;

                // **MODIFICATION: ONLY process sitemaps containing "post-sitemap"**
                if (!str_contains($linkedSitemapUrl, 'post-sitemap')) {
                    echo "  -> Skipping sitemap (not a post-sitemap): {$linkedSitemapUrl}\n";
                    continue; // Skip to the next entry
                }

                echo "  -> Fetching linked sitemap: {$linkedSitemapUrl}\n";

                // Polite delay before fetching next linked sitemap
                sleep(self::POLITE_DELAY_SECONDS);

                try {
                    $linkedContent = $this->fetchContentWithRetry($linkedSitemapUrl, self::MAX_RETRIES);
                    // Recursively call to get URLs from the linked sitemap
                    $urls = array_merge($urls, $this->extractUrlsFromSitemap($linkedContent));
                } catch (\Exception $e) {
                    echo "Warning: Failed to process linked sitemap {$linkedSitemapUrl}. Skipping.\n";
                }
            }
        }
        // Check for URL set (<urlset><url><loc>...</url></urlset>)
        elseif (isset($xml->url)) {
            echo "Found URL set. Extracting " . count($xml->url) . " URLs...\n";
            foreach ($xml->url as $urlEntry) {
                $urls[] = (string)$urlEntry->loc;
            }
        } else {
            echo "Warning: XML structure is neither Sitemap Index nor URL Set.\n";
        }

        return array_unique($urls);
    }

    /**
     * Fetches the HTML content for a single URL and extracts the filtered title.
     *
     * @param string $url The article URL.
     * @return array|null An array containing 'title' and 'link', or null if invalid.
     */
    private function fetchArticleTitle(string $url): ?array
    {
        echo "Checking URL: {$url}";
        // Only 1 attempt for article content, as we are already managing a polite delay
        try {
            $htmlContent = $this->fetchContentWithRetry($url, 1);
        } catch (\Exception $e) {
            echo " -> Failed to fetch article content. Skipping.\n";
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

        echo " -> Title is invalid or missing. Skipping.\n";
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
