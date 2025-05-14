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
            dd($feedUrls);
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
        $parsed_url = parse_url($targetUrl);
        if (isset($parsed_url['scheme']) && isset($parsed_url['host'])) {
            $main_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            $http_domain = 'http://' . $parsed_url['host'];
            $sitemapUrl = $main_domain . '/sitemap.xml';
        }
        // context options
        $arrContextOptions = array('http' => ['method' => "GET", 'header' => "User-Agent: curl/7.68.0\r\n", 'ignore_errors' => true], "ssl" => array("verify_peer" => false, "verify_peer_name" => false,));
        // load xml from sitemap.xml
        $items = [];
        $xml = simplexml_load_file($sitemapUrl);
        if (!$xml) {
            $sitemapContent = file_get_contents($sitemapUrl, false, stream_context_create($arrContextOptions));
            if (!empty($sitemapContent)) {
                $xml = simplexml_load_string($sitemapContent);
            }
        }
        if (count($xml) > 0) {
            $filteredSitemaps = [];
            $count = 0;
            foreach ($xml->sitemap as $sitemap) {
                if ($count >= $max) {
                    break;
                }
                $loc = (string) $sitemap->loc;
                // Check if the <loc> element contains "post-sitemap" or "sitemap-post"
                if (strpos($loc, "post-sitemap") !== false || strpos($loc, "sitemap-post") !== false || strpos($loc, "sitemap-") !== false) {
                    $filteredSitemaps[] = $sitemap;
                }
            }
            usort($filteredSitemaps, function ($a, $b) {
                $numberA = intval(preg_replace('/\D/', '', $a->loc));
                $numberB = intval(preg_replace('/\D/', '', $b->loc));
                return $numberB - $numberA; // Sort in descending order
            });
            $selectedSitemap = $filteredSitemaps[0];
            $loc = (string) $selectedSitemap->loc;
            if (
                strpos($loc, "post-sitemap") !== false ||
                strpos($loc, "sitemap-post") !== false ||
                strpos($loc, "sitemap-") !== false
            ) {
                $sitemapUrl = $loc; // Use the filtered URL
                $sitemapXml = simplexml_load_file($sitemapUrl);
                if (!$sitemapXml) {
                    $sitemapContent = file_get_contents($sitemapUrl, false, stream_context_create($arrContextOptions));
                    if (!empty($sitemapContent)) {
                        $sitemapXml = simplexml_load_string($sitemapContent);
                    }
                }
                // Now here we will sort the URL in descending order based on the last modified date so we will get the latest posts first //
                $urlLastModArray = [];
                foreach ($sitemapXml->url as $url) {
                    $urlString = (string) $url->loc;
                    $lastModString = (string) $url->lastmod;
                    $lastModTimestamp = strtotime($lastModString);
                    // Store URLs and last modification dates in a multidimensional array
                    $urlLastModArray[$lastModTimestamp][] = [
                        'loc' => $urlString,
                        'lastmod' => $lastModString
                    ];
                }
                // Sort the multidimensional array by keys (last modification dates) in descending order
                krsort($urlLastModArray);
                // Create a new SimpleXMLElement object to mimic the original structure
                $newSitemapXml = new SimpleXMLElement('<urlset></urlset>');
                foreach ($urlLastModArray as $lastModTimestamp => $urls) {
                    foreach ($urls as $urlData) {
                        $urlNode = $newSitemapXml->addChild('url');
                        $urlNode->addChild('loc', $urlData['loc']);
                        $urlNode->addChild('lastmod', $urlData['lastmod']);
                    }
                }
                // descending order complete with same structure as xml//
                foreach ($newSitemapXml->url as $url) {
                    $utmPostUrl = '';
                    if ($count >= $max) {
                        break;
                    }
                    $postUrl = (string) $url->loc; // Cast to string to get the URL
                    if ($postUrl == $main_domain . '/' || $postUrl == $http_domain . '/') {
                        continue; // Skip the first iteration
                    }
                    $info = $this->dom->get_info($postUrl, $this->data["mode"]);
                    $items[] = [
                        "link" => $postUrl,
                        "title" => $info["title"],
                        "image" => $info["image"],
                    ];
                }
                $response = [
                    'success' => true,
                    'data' => $items
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Sitemap Data not found!'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to fetch the RSS feed'
            ];
        }
        return $response;
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
