<?php
// app/Services/LinkPreviewService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class LinkPreviewService
{
    /**
     * The User-Agent string to mimic a known social media crawler (like Publer/Facebook).
     */
    protected const USER_AGENT = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';

    /**
     * Fetches and parses a URL to generate a link preview object.
     *
     * @param string $url The URL to fetch.
     * @return array The link preview data.
     */
    public function fetch(string $url): array
    {
        try {
            // 1. Fetch the HTML content using a specific User-Agent
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            ])->get($url);

            if ($response->failed()) {
                // Log or handle non-200 status codes
                return $this->getEmptyPreview($url);
            }

            // 2. Parse the HTML using Symfony's DomCrawler
            $crawler = new Crawler($response->body(), $url);

            $data = [
                'url'         => $url,
                'title'       => $this->getMetaContent($crawler, 'og:title'),
                'description' => $this->getMetaContent($crawler, 'og:description'),
                'image'       => $this->getMetaContent($crawler, 'og:image'),
                'site_name'   => $this->getMetaContent($crawler, 'og:site_name'),
            ];

            // 3. Apply Fallbacks (Mimicking Publer's secondary tags/title logic)
            $data['title'] = $data['title'] ?: $this->getStandardTitle($crawler);
            $data['description'] = $data['description'] ?: $this->getMetaContent($crawler, 'description');

            return $data;
        } catch (\Exception $e) {
            // In a real application, you'd log the exception here:
            // \Log::error("Link preview fetch failed for {$url}: " . $e->getMessage());
            return $this->getEmptyPreview($url, $e->getMessage());
        }
    }

    /**
     * Helper to extract content from a meta tag (e.g., og:title).
     *
     * @param Crawler $crawler
     * @param string $property The property name (e.g., 'og:title', 'description').
     * @return string|null
     */
    protected function getMetaContent(Crawler $crawler, string $property): ?string
    {
        $selector = str_starts_with($property, 'og:')
            ? "meta[property=\"{$property}\"]"
            : "meta[name=\"{$property}\"]";

        // The filter will return the content attribute of the first matching meta tag
        $content = $crawler->filter($selector)->extract(['content']);

        return !empty($content[0]) ? trim($content[0]) : null;
    }

    /**
     * Fallback to the standard <title> tag.
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function getStandardTitle(Crawler $crawler): ?string
    {
        try {
            return trim($crawler->filter('title')->text());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Returns a standardized empty preview array on failure.
     *
     * @param string $url
     * @param string $error
     * @return array
     */
    protected function getEmptyPreview(string $url, string $error = 'Failed to fetch or parse content.'): array
    {
        return [
            'url'         => $url,
            'title'       => null,
            'description' => null,
            'image'       => null,
            'site_name'   => null,
            'error'       => $error,
        ];
    }
}
