<?php

namespace App\Services;

use Exception;
use App\Models\Post;
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
            "user_id" => isset($data["user_id"]) ? $data["user_id"] : '',
            "account_id" => isset($data["account_id"]) ? $data["account_id"] : '',
            "type" => isset($data["type"]) ? $data["type"] : '',
            "domain_id" => isset($data["domain_id"]) ? $data["domain_id"] : '',
            "url" => isset($data["url"]) ? $data["url"] : '',
        ];
        $pinterestDimensions = pinterestDimensions();
        $this->heightArray = $pinterestDimensions["height"];
        $this->widthArray = $pinterestDimensions["width"];
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
                    $nextTime = $this->post->nextTime(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["soruce"], "type" => $this->data["type"]], $this->data["time"]);
                    $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["soruce"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $item["link"]])->first();
                    if (!$post) {
                        $post_row = $this->post->create([
                            "user_id" => $this->data["user_id"],
                            "account_id" => $this->data["account_id"],
                            "social_type" => $this->data["social_type"],
                            "type" => $this->data["type"],
                            "source" => $this->data["source"],
                            "title" => $item["title"],
                            "domain_id" => $this->data["domain_id"],
                            "url" => $item["link"],
                            "image" => isset($item["image"]) ? $item["image"] : no_image(),
                            "publish_date" => newDateTime($nextTime, $this->data["time"]),
                            "status" => 0,
                        ]);
                        $post_row->photo()->create([
                            "mode" => $this->data['social_type'],
                            "url" => $item["link"]
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
        try {
            $url = $targetUrl;
            $feed = FeedReader::read($url);
            $items = array_slice($feed->get_items(), 0, $max);
            $posts = [];
            foreach ($items as $item) {
                $title = $item->get_title();
                $link = $item->get_link();
                // $image = $this->extractImageFromRssItem($item, $this->widthArray, $this->heightArray);

                $posts[] = [
                    'title' => $title,
                    'link' => $link,
                ];
            }
            $response = [
                "success" => true,
                "data" => $posts
            ];
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return $response;
    }

    public function fetchSitemap(string $targetUrl, int $max = 10)
    {
        $sitemapUrl = $targetUrl . '/sitemap.xml';
        try {
            $response = Http::get($sitemapUrl);
            $response->throw();
            $xmlString = $response->body();
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
        try {
            $xml = @simplexml_load_string($xmlString);
            if ($xml === false) {
                $errorString = '';
                foreach (libxml_get_errors() as $error) {
                    $errorString .= "\t" . $error->message;
                }
                libxml_clear_errors();
                return [
                    "success" => false,
                    "message" => $errorString
                ];
            }

            $posts = [];
            $count = 0;
            foreach ($xml->sitemap as $sitemapEntry) {
                $childSitemapUrl = (string) $sitemapEntry->loc;
                if (!empty($childSitemapUrl)) {
                    $childXmlContent = $this->fetchUrlContent($childSitemapUrl);
                    if ($childXmlContent !== false) {
                        libxml_use_internal_errors(true);
                        $xml = simplexml_load_string($childXmlContent);
                        libxml_clear_errors();
                        foreach ($xml->url as $url) {
                            if ($count >= $max) {
                                break;
                            }
                            if ($targetUrl != $url->loc) {
                                $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["soruce"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $url->loc])->first();
                                if (!$post) {
                                    $invalid_titles = [
                                        "bot verification",
                                        "admin"
                                    ];
                                    $rss = $this->dom->get_info($url->loc, $this->data["mode"]);
                                    if (!in_array($rss, $invalid_titles)) {
                                        if (isset($rss["title"])) {
                                            $posts[] = [
                                                'title' => $rss["title"],
                                                'link' => (string) $url->loc,
                                            ];
                                            $count++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return [
                "success" => true,
                "data" => $posts
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function fetchUrlContent(string $url)
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
