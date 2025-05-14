<?php

namespace App\Services;

use Feed;
use Exception;
use App\Models\Post;
use SimpleXMLElement;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class TestFeedService
{
    private $post;
    private $dom;
    private $data;
    private $body;
    public function __construct($data)
    {
        $this->post = new Post();
        $this->dom = new HtmlParseService();
        $this->data = $data;
        $this->body = [
            "user_id" => $data["user_id"],
            "account_id" => $data["account_id"],
            "type" => $data["type"],
            "domain_id" => $data["domain_id"],
            "url" => $data["url"],
        ];
    }
    public function fetch()
    {
        $websiteUrl = $this->data["url"];
        if ($this->data["exist"]) {
            $feedUrls = $this->fetchSitemap($websiteUrl);
        } else {
            $feedUrls = $this->fetchRss($websiteUrl);
        }
        if ($feedUrls["success"]) {
            try {
                $items = $feedUrls["data"];
                foreach ($items as $key => $item) {
                    $nextTime = $this->post->nextTime(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"]], $this->data["time"]);
                    $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $item["link"]])->first();
                    if (!$post) {
                        $this->post->create([
                            "user_id" => $this->data["user_id"],
                            "account_id" => $this->data["account_id"],
                            "type" => $this->data["type"],
                            "title" => $item["title"],
                            "description" => "",
                            "domain_id" => $this->data["domain_id"],
                            "url" => $item["link"],
                            "image" => isset($item["image"]) ? $item["image"] : no_image(),
                            "publish_date" => newDateTime($nextTime, $this->data["time"], $key - 1),
                            "status" => 0,
                        ]);
                    }
                }
                return array(
                    "success" => true,
                    "items" => $items
                );
            } catch (Exception $e) {
                $this->body["message"] = $e->getMessage();
                create_notification($this->data["user_id"], $this->body, "Post");
                return array(
                    "success" => false,
                    "message" =>  $this->body["message"]
                );
            }
        } else {
            $this->body["message"] = $feedUrls["message"];
            create_notification($this->data["user_id"], $this->body, "Post");
            return array(
                "success" => false,
                "message" =>  $this->body["message"]
            );
        }
    }

    private function fetchRss($websiteUrl)
    {
        if (str_contains($websiteUrl, 'feed') || str_contains($websiteUrl, 'rss')) {
            $urlToCheck = $websiteUrl;
        } else {
            $urlToCheck = rtrim($websiteUrl, '/') . '/feed';
        }
        $fetchFeed = $this->fetchFeed($urlToCheck);
        return $fetchFeed;
    }

    /**
     * Attempt to discover feed or sitemap URLs (Basic Example).
     * A more robust implementation would involve fetching the HTML
     * and parsing <link> tags or checking robots.txt.
     *
     * @param string $websiteUrl
     * @return array
     */
    private function fetchSitemap(string $websiteUrl): array
    {
        $urlToCheck = rtrim($websiteUrl, '/') . '/sitemap.xml';
        $discoveredUrls = '';
        try {
            $response = Http::head($urlToCheck);
            if ($response->successful()) {
                // Check content type if possible (more reliable)
                $contentType = strtolower($response->header('Content-Type') ?? '');
                if (str_contains($contentType, 'xml')) {
                    $discoveredUrls = $urlToCheck;
                }
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went wrong!"
                );
            }
            if ($discoveredUrls) {
                $sitemap = Http::withHeaders(['User-Agent' => 'Engagyo RSS bot'])->get($discoveredUrls);
                if ($sitemap->successful()) {
                    $xmlContent = $sitemap->body();
                    $items = $this->parseContent($xmlContent, $websiteUrl);
                    if ($items["success"]) {
                        $response = [
                            "success" => true,
                            "data" => $items["data"]
                        ];
                    } else {
                        $response = [
                            "success" => false,
                            "message" => $items["message"]
                        ];
                    }
                } else {
                    $response = [
                        "success" => false,
                        "message" => "Failed to fetch feed/sitemap from {$websiteUrl}"
                    ];
                }
            } else {
                $response = [
                    "success" => false,
                    "message" => "Couldn't find Sitemap Data!"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }

        return $response;
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
            $items = [];
            if ($xml !== false) {
                if (isset($xml->sitemap)) {
                    foreach ($xml->sitemap as $sitemapEntry) {
                        $childSitemapUrl = (string) $sitemapEntry->loc;
                        if (!empty($childSitemapUrl)) {
                            $childXmlContent = $this->fetchUrlContent($childSitemapUrl);
                            if ($childXmlContent !== false) {
                                libxml_use_internal_errors(true);
                                $xml = simplexml_load_string($childXmlContent);
                                libxml_clear_errors();
                                $count = 1;
                                foreach ($xml->url as $url) {
                                    if (count($items) >= 20) {
                                        break;
                                    }
                                    $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $url->loc])->first();
                                    if (!$post) {
                                        $rss = $this->dom->get_info($url->loc, $this->data["mode"]);
                                        if (isset($rss["title"]) && !empty($rss["title"])) {
                                            $items[] = [
                                                'title' => $rss["title"],
                                                'link' => (string) $url->loc,
                                                'image' => $rss["image"],
                                            ];
                                            $count++;
                                        }
                                    }
                                }
                            }
                        }
                        if (count($items) >= 20) {
                            break;
                        }
                    }
                }

                if (isset($xml->channel->item)) {
                    $count = 1;
                    foreach ($xml->channel->item as $key => $item) {
                        if (count($items) >= 20) {
                            break;
                        }
                        $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $item->link])->first();
                        if (!$post) {
                            $rss = $this->dom->get_info($item->link, $this->data["mode"]);
                            if (isset($rss["title"]) && !empty($rss["title"])) {
                                $items[] = [
                                    'title' => (string) $item->title,
                                    'link' => (string) $item->link,
                                    'image' => $rss["image"]
                                ];
                            }
                        }
                    }
                }
                $response = [
                    "success" => true,
                    "data" => $items
                ];
            } else {
                $response = [
                    "success" => false,
                    "message" => "Failed to parse XML from {$sourceUrl}"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return $response;
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
    }

    /**
     * Fetch RSS feed and extract data including images.
     *
     * @param string $feedUrl The URL of the RSS feed.
     * @return array An array of feed items with title, link, and image.
     */
    public function fetchFeed(string $feedUrl): array
    {
        $items = [];
        try {
            // Fetch the RSS feed content
            $response = Http::get($feedUrl);
            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                if ($xml === false) {
                    return [
                        "success" => false,
                        "message" => "Failed to parse RSS feed XML from: " . $feedUrl
                    ];
                }
                // Determine if the feed is likely from Pinterest based on URL or other indicators
                $isPinterestFeed = $this->data["mode"] ? true : false;
                // Define preferred Pinterest image dimensions
                $pinterestPreferredHeights = ["1128", "900", "1000", "1024", "1349"];
                $pinterestPreferredWidths = ["564", "700", "1500", "512", "759"];
                foreach ($xml->channel->item as $item) {
                    $title = (string) $item->title;
                    $link = (string) $item->link;
                    $imageUrl = null;
                    // --- Image Extraction Logic ---
                    if ($isPinterestFeed) {
                        // Logic for Pinterest feeds
                        $imageUrl = $this->findPinterestImage($item, $pinterestPreferredWidths, $pinterestPreferredHeights);
                        // If no preferred Pinterest image found, try finding a thumbnail
                        if (!$imageUrl) {
                            $imageUrl = $this->findImageInContent($item);
                        }
                    } else {
                        // Logic for non-Pinterest feeds - look for common image tags
                        $imageUrl = $this->findImageInContent($item);
                    }
                    // If no image found at all, maybe look in description/content (more complex parsing needed)
                    if (!$imageUrl) {
                        $imageUrl = $this->findImageInContent($feedUrl);
                    }
                    $items[] = [
                        'title' => $title,
                        'link' => $link,
                        'image' => $imageUrl,
                    ];
                }
            } else {
                return [
                    "success" => false,
                    "message" => "Failed to fetch RSS feed from: " . $feedUrl
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return [
            "success" => true,
            "data" => $items
        ];
    }

    /**
     * Find a Pinterest image with preferred dimensions.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @param array $preferredWidths Array of preferred widths.
     * @param array $preferredHeights Array of preferred heights.
     * @return string|null The image URL if found, otherwise null.
     */
    protected function findPinterestImage(SimpleXMLElement $item, array $preferredWidths, array $preferredHeights): ?string
    {
        // Pinterest often uses media:content or includes images in description/content
        // We'll prioritize media:content with dimensions if available
        // Check media:content
        $pin_url = null;
        if (isset($item->children('media', true)->content)) {
            foreach ($item->children('media', true)->content as $content) {
                $attributes = $content->attributes();
                if (isset($attributes['url']) && isset($attributes['width']) && isset($attributes['height'])) {
                    $url = (string) $attributes['url'];
                    $width = (string) $attributes['width'];
                    $height = (string) $attributes['height'];

                    if (in_array($width, $preferredWidths) && in_array($height, $preferredHeights)) {
                        $pin_url = $url;
                    }
                }
            }
        }
        // If no preferred size image in media:content, check other potential image locations
        // (You might need to add more specific logic here based on actual Pinterest feed structure)

        return $pin_url; // No preferred Pinterest image found
    }

    /**
     * Find a thumbnail image.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @return string|null The thumbnail URL if found, otherwise null.
     */
    protected function findThumbnail(SimpleXMLElement $item): ?string
    {
        // Check media:thumbnail
        if (isset($item->children('media', true)->thumbnail)) {
            $attributes = $item->children('media', true)->thumbnail->attributes();
            if (isset($attributes['url'])) {
                return (string) $attributes['url'];
            }
        }
        return null; // No thumbnail found
    }


    /**
     * Find a generic image in common RSS tags.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @return string|null The image URL if found, otherwise null.
     */
    protected function findGenericImage(SimpleXMLElement $item): ?string
    {
        // Check media:thumbnail
        if (isset($item->children('media', true)->thumbnail)) {
            $attributes = $item->children('media', true)->thumbnail->attributes();
            if (isset($attributes['url'])) {
                return (string) $attributes['url'];
            }
        }
        if (isset($item->image)) {
            // Assuming the <image> tag has a <url> child
            if (isset($item->image->url)) {
                return (string) $item->image->url;
            }
            // Or if the <image> tag itself has a url attribute (less standard)
            $attributes = $item->image->attributes();
            if (isset($attributes['url'])) {
                return (string) $attributes['url'];
            }
        }
        // Add more checks for other common image tags if needed (e.g., <image>)
        return null; // No generic image found in common tags
    }

    /**
     * Find an image within the item's description or content.
     * This requires parsing HTML content within the XML.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @return string|null The image URL if found, otherwise null.
     */
    protected function findImageInContent(string $feedUrl): ?string
    {
        $rss = $this->dom->get_info($feedUrl, $this->data["mode"]);
        $image = $rss["image"];
        return $image;
    }

    /**
     * Helper function to get image dimensions from a URL.
     * Note: This requires fetching image data and can be slow.
     * Consider if this is necessary or if dimensions can be extracted from the feed itself.
     *
     * @param string $imageUrl The URL of the image.
     * @return array|false An array [width, height] or false on failure.
     */
    protected function getImageDimensions(string $imageUrl): array|false
    {
        // Use getimagesize to get dimensions. This fetches part of the image.
        // Be cautious with performance if calling this for many images.
        // Ensure the URL is accessible and getimagesize is enabled in PHP config.
        $dimensions = @getimagesize($imageUrl);
        if ($dimensions) {
            return ['width' => $dimensions[0], 'height' => $dimensions[1]];
        }
        return false;
    }
}
