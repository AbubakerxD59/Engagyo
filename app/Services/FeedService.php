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

    public function fetchSitemap(string $targetUrl, int $max = 10, array $postPatterns = ['/blog/', '/posts/', '/article/'])
    {
        $sitemapUrl = $targetUrl . '/sitemap.xml';
        // try {
        // 1. Fetch the sitemap content
        $response = Http::get($sitemapUrl);

        // Check for HTTP errors (4xx or 5xx)
        $response->throw();

        $xmlString = $response->body();
        // } catch (RequestException $e) {
        //     // Handle HTTP request errors
        //     throw new Exception("Failed to fetch sitemap from {$sitemapUrl}: " . $e->getMessage());
        // } catch (Exception $e) {
        //     // Catch other potential errors during fetch
        //     throw new Exception("An error occurred while fetching sitemap: " . $e->getMessage());
        // }
        // try {
        // 2. Parse the XML
        // Suppress errors with @ and check the return value as simplexml_load_string returns false on failure
        $xml = @simplexml_load_string($xmlString);

        if ($xml === false) {
            // Get XML errors if parsing failed
            $errorString = '';
            foreach (libxml_get_errors() as $error) {
                $errorString .= "\t" . $error->message;
            }
            libxml_clear_errors(); // Clear errors for subsequent XML operations
            throw new Exception("Failed to parse sitemap XML. Errors: \n" . $errorString);
        }


        $postUrls = [];
        $count = 0;
        // Check if the root element is 'urlset' as expected for a standard sitemap
        // 3. Extract, Filter, and Select URLs
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
                        dd($url);
                        if ($count >= $max) {
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
            if (isset($urlElement->loc)) {
                $loc = (string) $urlElement->loc;
                // Filter URLs based on patterns
                $isPost = false;
                foreach ($postPatterns as $pattern) {
                    if (str_contains($loc, $pattern)) {
                        $isPost = true;
                        break; // Found a pattern, no need to check others
                    }
                }
                dd($loc, $isPost);

                if ($isPost) {
                    $$postUrls[] = $loc;
                    // Stop once we have the desired number of posts
                    if (count($postUrls) >= $max) {
                        break;
                    }
                }
            }
        }


        return $postUrls;
        // } catch (Exception $e) {
        //     // Catch errors during parsing or processing
        //     throw new Exception("An error occurred while processing sitemap XML: " . $e->getMessage());
        // }
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
