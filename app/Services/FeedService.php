<?php

namespace App\Services;

use Feed;
use Exception;
use App\Models\Post;
use SimpleXMLElement;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Vedmant\FeedReader\Facades\FeedReader;


class FeedService
{
    private $post;
    private $dom;
    private $data;
    private $body;
    private $heightArray = [];
    private $widthArray = [];
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
        $this->heightArray = array("1128", "900", "1000", "1024", "1349");
        $this->widthArray = array("564", "700", "1500", "512", "759");
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

    private function fetchRss($url)
    {
        $feed = FeedReader::read($url);
        $items = array_slice($feed->get_items(), 0, 10);

        foreach ($items as $item) {
            $title = $item->get_title();
            $link = $item->get_link();
            $image = $this->extractImageFromRssItem($item);

            $posts[] = [
                'title' => $title,
                'link' => $link,
                'image' => $image,
            ];
        }
        return [
            "success" => true,
            "data" => $posts
        ];
    }

    /**
     * Attempt to discover feed or sitemap URLs (Basic Example).
     * A more robust implementation would involve fetching the HTML
     * and parsing <link> tags or checking robots.txt.
     *
     * @param string $websiteUrl
     * @return array
     */
    private function fetchSitemap(string $url, int $maxPosts = 20): array
    {
        $url = $url . '/sitemap.xml';
        $response = Http::get($url);
        $posts = [];
        if ($response->successful()) {
            $xmlContent = $response->body();
            $xml = new SimpleXMLElement($xmlContent);
            // Namespaces can be tricky with sitemaps. Adjust if necessary.
            $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $items = $xml->xpath('//s:url'); // Common sitemap structure
            dd($xml, $items);

            // If the above doesn't work, try without namespace or inspect sitemap structure
            if (empty($items)) {
                $items = $xml->xpath('//url');
            }
            $count = 0;
            foreach ($items as $item) {
                if ($count >= $maxPosts) {
                    break;
                }
                $link = (string)$item->loc;
                // Sitemap usually doesn't contain title or direct image.
                // You might need to fetch the page content to get these.
                // For simplicity, we'll try to derive a title or leave it blank.
                $title = $this->fetchTitleFromUrl($link) ?: 'Title not found';
                $image = $this->fetchImageFromUrl($link);
                $posts[] = [
                    'title' => $title,
                    'link' => $link,
                    'image' => $image,
                ];
                $count++;
            }
            return [
                "success" => true,
                "data" => $posts
            ];
        } else {
            return [
                "success" => false,
                "data" => 'Failed to fetch sitemap'
            ];
        }
    }

    /**
     * Extracts image from an RSS item.
     * Prioritizes Pinterest-specific images, then thumbnails.
     */
    private function extractImageFromRssItem($item): ?string
    {
        $potentialImages = [];

        // 1. Check for media:content or enclosure tags (often higher quality)
        $enclosures = $item->get_enclosures();
        if ($enclosures) {
            foreach ($enclosures as $enclosure) {
                if (strpos($enclosure->get_type(), 'image') !== false && $enclosure->get_link()) {
                    $potentialImages[] = $enclosure->get_link();
                }
                // SimplePie specific for thumbnails in enclosures
                if ($enclosure->get_thumbnail()) {
                    $potentialImages[] = $enclosure->get_thumbnail();
                }
            }
        }

        // 2. Check for media:thumbnail
        $media_thumbnail = $item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
        if (!empty($media_thumbnail) && isset($media_thumbnail[0]['attribs']['']['url'])) {
            $potentialImages[] = $media_thumbnail[0]['attribs']['']['url'];
        }

        // 3. Look for images in the description or content
        $description = $item->get_description();
        $content = $item->get_content();

        if ($description) {
            preg_match_all('/<img[^>]+src="([^">]+)"/i', $description, $matches);
            if (!empty($matches[1])) {
                $potentialImages = array_merge($potentialImages, $matches[1]);
            }
        }
        if ($content) {
            preg_match_all('/<img[^>]+src="([^">]+)"/i', $content, $matches);
            if (!empty($matches[1])) {
                $potentialImages = array_merge($potentialImages, $matches[1]);
            }
        }

        // 4. Check image from <image> tag or <itunes:image> if available directly on item (less common for items, more for channel)
        // The vedmant/laravel-feed-reader might expose these differently or not at all at item level.

        $potentialImages = array_unique(array_filter($potentialImages));

        // Check for Pinterest-specific dimensions
        foreach ($potentialImages as $imgUrl) {
            $dimensions = @getimagesize($imgUrl); // Use @ to suppress errors for invalid images
            if ($dimensions && is_array($dimensions)) {
                list($width, $height) = $dimensions;
                if (in_array((string)$width, $this->widthArray) && in_array((string)$height, $this->heightArray)) {
                    return $imgUrl; // Found Pinterest specific image
                }
            }
        }

        // If no Pinterest image, return the first valid image as a thumbnail
        foreach ($potentialImages as $imgUrl) {
            if (filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                // Further check if it's a real image if necessary
                $headers = @get_headers($imgUrl, 1);
                if ($headers && isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'image/') !== false) {
                    return $imgUrl;
                }
            }
        }

        return null; // No suitable image found
    }


    /**
     * Fallback to fetch title from a URL (basic implementation)
     * For sitemaps, as they usually only contain URLs.
     */
    private function fetchTitleFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(5)->get($url); // Set a timeout
            if ($response->successful()) {
                $htmlContent = $response->body();
                if (preg_match('/<title>(.*?)<\/title>/is', $htmlContent, $matches)) {
                    return trim($matches[1]);
                }
            }
        } catch (\Throwable $e) {
            // Log error or handle silently
        }
        return null;
    }

    /**
     * Fallback to fetch image from a URL's content (basic implementation)
     * For sitemaps or if RSS item has no direct image.
     */
    private function fetchImageFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful()) {
                $htmlContent = $response->body();
                $potentialImages = [];

                preg_match_all('/<img[^>]+src="([^">]+)"/i', $htmlContent, $matches);
                if (!empty($matches[1])) {
                    $potentialImages = array_map(function ($imgUrl) use ($url) {
                        // Convert relative URLs to absolute
                        if (strpos($imgUrl, 'http') !== 0) {
                            $urlParts = parse_url($url);
                            $baseUrl = $urlParts['scheme'] . '://' . $urlParts['host'];
                            if (strpos($imgUrl, '/') === 0) { // Starts with /
                                return $baseUrl . $imgUrl;
                            } else { // Relative path
                                $path = isset($urlParts['path']) ? dirname($urlParts['path']) : '';
                                return $baseUrl . rtrim($path, '/') . '/' . $imgUrl;
                            }
                        }
                        return $imgUrl;
                    }, $matches[1]);
                }

                // Also check for OpenGraph image
                if (preg_match('/<meta[^>]+property="og:image"[^>]+content="([^">]+)"/i', $htmlContent, $ogMatches)) {
                    if (!empty($ogMatches[1])) {
                        array_unshift($potentialImages, $ogMatches[1]); // Prioritize OG image
                    }
                }

                $potentialImages = array_unique(array_filter($potentialImages));

                // Check for Pinterest-specific dimensions
                foreach ($potentialImages as $imgUrl) {
                    if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) continue;
                    $dimensions = @getimagesize($imgUrl);
                    if ($dimensions && is_array($dimensions)) {
                        list($width, $height) = $dimensions;
                        if (in_array((string)$width, $this->widthArray) && in_array((string)$height, $this->heightArray)) {
                            return $imgUrl;
                        }
                    }
                }

                // If no Pinterest image, return the first valid image (e.g., OG image or first img tag)
                foreach ($potentialImages as $imgUrl) {
                    if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) continue;
                    $headers = @get_headers($imgUrl, 1);
                    if ($headers && isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'image/') !== false) {
                        return $imgUrl;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log error or handle silently
        }
        return null;
    }
}
