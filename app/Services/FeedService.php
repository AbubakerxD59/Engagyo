<?php

namespace App\Services;

use App\Models\Post;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class FeedService
{
    private $post;
    private $dom;
    public function __construct()
    {
        $this->post = new Post();
        $this->dom = new HtmlParseService();
    }
    public function fetch($url, $domain, $user, $account_id, $type, $time, $mode = 0)
    {
        $websiteUrl = $url;
        $feedUrls = $this->discoverFeedUrls($websiteUrl);
        if (empty($feedUrls)) {
            return array(
                "success" => false,
                'error' => 'Could not find RSS/Atom feed or Sitemap URL.'
            );
        }
        $targetUrl = $feedUrls[0];

        // try {
            $response = Http::timeout(5) // Set a reasonable timeout
                ->withHeaders(['User-Agent' => 'Engagyo RSS bot']) // Be polite, identify your bot
                ->get($targetUrl);

            if (!$response->successful()) {
                Log::error("Failed to fetch feed/sitemap from {$targetUrl}. Status: " . $response->status());
                return array(
                    "success" => false,
                    'error' => 'Failed to fetch content from the target URL.'
                );
            }
            $xmlContent = $response->body();
            $items = $this->parseContent($xmlContent, $targetUrl);
            foreach ($items as $key => $item) {
                $nextTime = $this->post->nextTime(["user_id" => $user->id, "account_id" => $account_id, "type" => $type, "domain_id" => $domain->id]);
                $post = $this->post->exist(["user_id" => $user->id, "account_id" => $account_id, "type" => $type, "domain_id" => $domain->id, "url" => $item["link"]])->notPublished()->first();
                $rss_image = $this->dom->get_info($item["link"], $mode);
                if (!$post) {
                    $this->post->create([
                        "user_id" => $user->id,
                        "account_id" => $account_id,
                        "type" => $type,
                        "title" => $item["title"],
                        "description" => $item["description"],
                        "domain_id" => $domain->id,
                        "url" => $item["link"],
                        "image" => $rss_image ? $rss_image["iamge"] : no_image(),
                        "publish_date" => newDateTime($nextTime, $time, $key - 1),
                        "status" => 0,
                    ]);
                }
            }
            return array(
                "success" => true,
                "items" => $items
            );
        // } catch (Exception $e) {
        //     Log::error("Error fetching or parsing feed/sitemap from {$targetUrl}: " . $e->getMessage());
        //     return array(
        //         "success" => false,
        //         'error' => 'An error occurred while processing the feed/sitemap.'
        //     );
        // }
    }

    /**
     * Attempt to discover feed or sitemap URLs (Basic Example).
     * A more robust implementation would involve fetching the HTML
     * and parsing <link> tags or checking robots.txt.
     *
     * @param string $websiteUrl
     * @return array
     */
    private function discoverFeedUrls(string $websiteUrl): array
    {
        $potentialPaths = [
            '/feed',
            '/rss',
            '/feed.xml',
            '/rss.xml',
            '/atom.xml',
            '/sitemap.xml', // Check sitemap too
        ];

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
    private function parseContent(string $xmlContent, string $sourceUrl): array
    {
        try {
            // Suppress errors during loading, check manually after
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            libxml_clear_errors(); // Clear errors from buffer

            if ($xml === false) {
                Log::warning("Failed to parse XML from {$sourceUrl}");
                return []; // Return empty if XML is invalid
            }

            $items = [];

            // Check root element or URL path to determine type (heuristic)
            if (isset($xml->channel->item)) { // RSS 2.0
                foreach ($xml->channel->item as $item) {
                    $items[] = [
                        'title' => (string) $item->title,
                        'link' => (string) $item->link,
                        'description' => (string) $item->description,
                        'pubDate' => isset($item->pubDate) ? (string) $item->pubDate : null,
                        // Add other fields as needed (guid, category, etc.)
                    ];
                }
            } elseif (isset($xml->entry)) { // Atom
                foreach ($xml->entry as $entry) {
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
                foreach ($xml->url as $url) {
                    $items[] = [
                        'title' => null, // Sitemaps usually don't have titles
                        'link' => (string) $url->loc,
                        'description' => null,
                        'pubDate' => isset($url->lastmod) ? (string) $url->lastmod : null,
                    ];
                }
            } elseif (isset($xml->sitemap)) { // Sitemap Index File
                // If it's a sitemap index, you might want to recursively fetch and parse the listed sitemaps
                Log::info("Detected Sitemap Index at {$sourceUrl}. Recursive parsing not implemented in this example.");
                // You could loop through $xml->sitemap, get the <loc>, and call fetch/parse again
            }


            return $items;
        } catch (Exception $e) {
            Log::error("Error parsing XML from {$sourceUrl}: " . $e->getMessage());
            return []; // Return empty on parsing error
        }
    }
}
