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
use Illuminate\Http\Client\RequestException;


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

    private function fetchRss(string $targetUrl, int $max = 10)
    {
        $url = $targetUrl;
        $feed = FeedReader::read($url);
        $items = array_slice($feed->get_items(), 0, $max);
        $posts = [];
        foreach ($items as $item) {
            $title = $item->get_title();
            $link = $item->get_link();
            $image = $this->extractImageFromRssItem($item, $this->widthArray, $this->heightArray);

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

    public function fetchSitemap(string $targetUrl, int $max = 10)
    {
        $sitemapUrl = $targetUrl . '/sitemap.xml';
        try {
            // Step 1: Fetch the Sitemap XML content
            $response = Http::timeout(30)->get($sitemapUrl); // 30-second timeout

            if (!$response->successful()) {
                return [
                    "success" => false,
                    "message" => "Failed to fetch sitemap!"
                ];
            }
            $xmlContent = $response->body();
            // Step 2: Parse the XML
            // Disable libxml errors and use internal error handling to prevent breaking the flow
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xmlContent);
            $xmlErrors = libxml_get_errors();
            libxml_clear_errors();

            if (!empty($xmlErrors)) {
                foreach ($xmlErrors as $error) {
                    return [
                        "success" => false,
                        "message" => $error->message
                    ];
                }
                // Decide if you want to fail here or try to proceed if partially parsed
            }

            if ($xml === false) {
                return [
                    "success" => false,
                    "message" => "Failed to parse sitemap XML!"
                ];
            }

            // Sitemaps can have namespaces. Common ones are 's' or none.
            // Register XPath namespace if needed (common for sitemaps)
            $namespaces = $xml->getDocNamespaces();
            $nsPrefix = ''; // Default to no namespace

            // Check for the standard sitemap namespace
            if (isset($namespaces['']) && $namespaces[''] == 'http://www.sitemaps.org/schemas/sitemap/0.9') {
                // It's the default namespace, no prefix needed for direct children, but XPath might need it.
                // Or, if a prefix is explicitly used in the sitemap:
                foreach ($namespaces as $prefix => $uri) {
                    if ($uri == 'http://www.sitemaps.org/schemas/sitemap/0.9') {
                        if ($prefix !== '') { // If there's an explicit prefix like <s:url>
                            $xml->registerXPathNamespace($prefix, $uri);
                            $nsPrefix = $prefix . ':';
                        }
                        break;
                    }
                }
            } else {
                // Fallback: attempt to register a common 's' prefix if no default namespace matches,
                // or if you observe 's:url' tags in your target sitemap.
                $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
                // And try with 's:' prefix if direct access or non-prefixed XPath fails.
            }


            // Step 3: Extract URLs (typically <loc> inside <url>)
            // Try with namespace first, then without if common sitemap structure is used.
            $items = $xml->xpath("//{$nsPrefix}url");
            if (empty($items) && $nsPrefix !== '') { // If prefixed search failed, try common non-prefixed if nsPrefix was derived from default
                $items = $xml->xpath("//url");
            } else if (empty($items) && $nsPrefix === '') { // If no namespace was detected or used, and '//url' failed, try with 's:'
                $items = $xml->xpath("//s:url"); // Try with common 's' prefix
                if (empty($items)) { // If still empty, try with no prefix at all
                    $items = $xml->xpath('//url');
                }
            }


            if (empty($items)) {
                return [
                    "success" => false,
                    "message" => "No <url> elements found in sitemap!"
                ];
            }

            $posts = [];
            $count = 0;
            foreach ($items as $item) {
                if ($count >= $max) {
                    break;
                }

                $locElement = $item->{$nsPrefix === '' ? 'loc' : $nsPrefix . 'loc'}; // Access <loc>
                if (empty($locElement) && $nsPrefix !== '') { // Try without prefix if it was explicitly set
                    $locElement = $item->loc;
                } else if (empty($locElement) && $nsPrefix === '' && isset($namespaces['s'])) { // If we tried no prefix but 's' was registered
                    $locElement = $item->children($namespaces['s'])->loc;
                }


                $url = (string)$locElement;

                if (empty($url)) {
                    // Log Linfo("Empty <loc> tag found in sitemap item: {$sitemapUrl}");
                    continue; // Skip if URL is empty
                }

                $info = $this->dom->get_info($url, $this->data["mode"]);
                // Sitemaps usually ONLY provide: URL, last modification, change frequency, priority.
                // To get title, actual content, or images, you need to fetch each URL.
                // This example focuses on getting the URLs from the sitemap itself.
                $postData = [
                    'url' => $url,
                    'title' => $info["title"],
                    'image' => $info["image"], // Placeholder: To be fetched from the URL itself
                ];

                // **IMPORTANT**: Fetching details for each URL can be slow.
                // Uncomment and enhance the following lines if you need title/image.
                /*
                try {
                    $pageResponse = Http::timeout(10)->get($url);
                    if ($pageResponse->successful()) {
                        $pageHtml = $pageResponse->body();
                        // Basic title extraction
                        if (preg_match('/<title>(.*?)<\/title>/is', $pageHtml, $titleMatches)) {
                            $postData['title'] = trim(html_entity_decode($titleMatches[1]));
                        }
                        // Basic image extraction (e.g., Open Graph image)
                        if (preg_match('/<meta[^>]+property="og:image"[^>]+content="([^">]+)"/i', $pageHtml, $imageMatches)) {
                            $postData['image'] = $imageMatches[1];
                        } elseif (preg_match('/<img[^>]+src="([^">]+)"/i', $pageHtml, $firstImageMatches)) {
                            // Fallback to first image tag (needs to be made absolute if relative)
                            // This is a very naive fallback
                            // $postData['image'] = $this->makeAbsoluteUrl($firstImageMatches[1], $url);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Could not fetch details for URL {$url}: " . $e->getMessage());
                }
                */

                $posts[] = $postData;
                $count++;
            }
            return [
                "success" => true,
                "data" => $posts
            ];
        } catch (RequestException $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    private function extractImageFromRssItem($item, array $pinterestWidths, array $pinterestHeights): ?string
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
                if (in_array((string)$width, $pinterestWidths) && in_array((string)$height, $pinterestHeights)) {
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
}
