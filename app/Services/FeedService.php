<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Post;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class FeedService
{
    private $post;
    private $dom;
    private $notification;
    public function __construct()
    {
        $this->post = new Post();
        $this->dom = new HtmlParseService();
        $this->notification = new Notification();
    }
    public function fetch($url, $domain_id, $user_id, $account_id, $type, $time, $mode = 0, $exist = true)
    {
        $websiteUrl = $url;
        $feedUrls = $this->discoverFeedUrls($websiteUrl, $exist);
        if (empty($feedUrls)) {
            $body = [
                "user_id" => $user_id,
                "account_id" => $account_id,
                "type" => $type,
                "domain_id" => $domain_id,
                "url" => $url,
                "message" => "Could not find RSS feed or Sitemap URL"
            ];
            create_notification($user_id, $body, "Post");
            exit;
        }
        $targetUrl = $feedUrls[0];
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'Engagyo RSS bot'])
                ->get($targetUrl);

            if (!$response->successful()) {
                $body = [
                    "user_id" => $user_id,
                    "account_id" => $account_id,
                    "type" => $type,
                    "domain_id" => $domain_id,
                    "url" => $url,
                    "message" => "Failed to fetch feed/sitemap from {$targetUrl}. Status: " . $response->status()
                ];
                create_notification($user_id, $body, "Post");
                exit;
            }
            $xmlContent = $response->body();
            $items = $this->parseContent($xmlContent, $targetUrl);
            dd($items, 'items');
            foreach ($items as $key => $item) {
                $nextTime = $this->post->nextTime(["user_id" => $user_id, "account_id" => $account_id, "type" => $type, "domain_id" => $domain_id]);
                $post = $this->post->exist(["user_id" => $user_id, "account_id" => $account_id, "type" => $type, "domain_id" => $domain_id, "url" => $item["link"]])->notPublished()->first();
                $rss_image = $this->dom->get_info($item["link"], $mode);
                if (!$post) {
                    $this->post->create([
                        "user_id" => $user_id,
                        "account_id" => $account_id,
                        "type" => $type,
                        "title" => $item["title"],
                        "description" => $item["description"],
                        "domain_id" => $domain_id,
                        "url" => $item["link"],
                        "image" => $rss_image ? $rss_image["image"] : no_image(),
                        "publish_date" => newDateTime($nextTime, $time, $key - 1),
                        "status" => 0,
                    ]);
                }
            }
            return array(
                "success" => true,
                "items" => $items
            );
        } catch (Exception $e) {
            $body = [
                "user_id" => $user_id,
                "account_id" => $account_id,
                "type" => $type,
                "domain_id" => $domain_id,
                "url" => $url,
                "message" => $e->getMessage()
            ];
            create_notification($user_id, $body, "Post");
        }
    }

    /**
     * Attempt to discover feed or sitemap URLs (Basic Example).
     * A more robust implementation would involve fetching the HTML
     * and parsing <link> tags or checking robots.txt.
     *
     * @param string $websiteUrl
     * @return array
     */
    private function discoverFeedUrls(string $websiteUrl, $exist = true): array
    {
        if ($exist) {
            $potentialPaths = [
                '/sitemap.xml',
            ];
        } else {
            $potentialPaths = [
                '/feed',
                '/rss',
                '/feed.xml',
                '/rss.xml',
            ];
        }

        $discoveredUrls = [];

        // Basic check for common paths
        foreach ($potentialPaths as $path) {
            $urlToCheck = rtrim($websiteUrl, '/') . $path;
            try {
                // Use HEAD request to check existence without downloading body
                $response = Http::timeout(5)->head($urlToCheck);
                if ($response->successful()) {
                    // Check content type if possible (more reliable)
                    $contentType = strtolower($response->header('Content-Type') ?? '');
                    if (str_contains($contentType, 'xml')) {
                        $discoveredUrls[] = $urlToCheck;
                        // Optional: Prioritize RSS/Atom over sitemap if both found
                    }
                }
            } catch (Exception $e) {
                // Ignore connection errors etc. for discovery
            }
        }

        // Add HTML parsing for <link> tags
        // Add robots.txt parsing for Sitemap: directive

        return $discoveredUrls; // Return found URLs
    }

    /**
     * Parse XML content from RSS/Atom feed or Sitemap.
     *
     * @param string $xmlContent
     * @param string $sourceUrl Used to determine parsing type
     * @return array
     */
    private function parseContent(string $xmlContent, string $sourceUrl, int $depth = 0, int $maxDepth = 5): array
    {
        try {
            // Suppress errors during loading, check manually after
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            libxml_clear_errors(); // Clear errors from buffer

            if ($xml === false) {
                $response = [
                    "success" => false,
                    "message" => "Failed to parse XML from {$sourceUrl}"
                ];
                return $response;
            }
            $items = [];
            // Check root element or URL path to determine type (heuristic)
            if (isset($xml->channel->item)) { // RSS 2.0
                foreach ($xml->channel->item as $key => $item) {
                    if ($key == 19) {
                        break;
                    }
                    $items[] = [
                        'title' => (string) $item->title,
                        'link' => (string) $item->link,
                        'description' => (string) $item->description,
                        'pubDate' => isset($item->pubDate) ? (string) $item->pubDate : null,
                        // Add other fields as needed (guid, category, etc.)
                    ];
                }
            } elseif (isset($xml->entry)) { // Atom
                foreach ($xml->entry as $key => $entry) {
                    if ($key == 19) {
                        break;
                    }
                    $link = '';
                    // Find the 'alternate' link
                    foreach ($entry->link as $linkNode) {
                        if (isset($linkNode['rel']) && (string)$linkNode['rel'] == 'alternate') {
                            $link = (string) $linkNode['href'];
                            break;
                        }
                    }
                    // Fallback to the first link if no 'alternate' found
                    if (empty($link) && isset($entry->link[0]['href'])) {
                        $link = (string) $entry->link[0]['href'];
                    }

                    $items[] = [
                        'title' => (string) $entry->title,
                        'link' => $link,
                        'description' => isset($entry->summary) ? (string) $entry->summary : (isset($entry->content) ? (string) $entry->content : null),
                        'pubDate' => isset($entry->updated) ? (string) $entry->updated : (isset($entry->published) ? (string) $entry->published : null),
                    ];
                }
            } elseif (isset($xml->url)) { // Sitemap
                foreach ($xml->url as $key => $url) {
                    if ($key == 19) {
                        break;
                    }
                    $items[] = [
                        'title' => null, // Sitemaps usually don't have titles
                        'link' => (string) $url->loc,
                        'description' => null,
                        'pubDate' => isset($url->lastmod) ? (string) $url->lastmod : null,
                    ];
                }
            } elseif (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemapEntry) {
                    $childSitemapUrl = (string) $sitemapEntry->loc;
                    if (!empty($childSitemapUrl)) {
                        $childXmlContent = $this->fetchUrlContent($childSitemapUrl);
                        if ($childXmlContent !== false) {
                            $childParseResult = $this->parseContent($childXmlContent, $childSitemapUrl, $depth + 1, $maxDepth);
                            if (is_array($childParseResult) && count($childParseResult) > 0) {
                                if (count($childParseResult) > 20) {
                                    $childParseResult = array_slice($childParseResult, 0, 20);
                                }
                                $items = array_merge($items, $childParseResult);
                            }
                        }
                    }
                    if (count($items) >= 20) {
                        break;
                    }
                }
            }
            return $items;
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
            return $response;
        }
    }

    private function fetchUrlContent(string $url)
    {
        $context = stream_context_create([
            'http' => ['timeout' => 30]
        ]);
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return false;
        }

        return $content;

        // Example implementation using cURL (more robust for production)

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set a timeout
        // // Add other cURL options as needed (e.g., SSL verification)

        // $content = curl_exec($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // if ($content === false || $httpCode >= 400) {
        //     // Log cURL error: curl_error($ch)
        //     if (class_exists('Log')) {
        //         Log::error("Failed to fetch URL (cURL): {$url}. HTTP Code: {$httpCode}. Error: " . curl_error($ch));
        //     } else {
        //         error_log("Failed to fetch URL (cURL): {$url}. HTTP Code: {$httpCode}. Error: " . curl_error($ch));
        //     }
        //     curl_close($ch);
        //     return false;
        // }

        // curl_close($ch);
        // return $content;
    }
}
