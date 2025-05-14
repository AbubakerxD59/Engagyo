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
        dd($feedUrls);
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
        $contextOptions = [
            'http' => [
                'user_agent' => $this->dom->user_agent(),
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($contextOptions);
        $file = file_get_contents($targetUrl, FALSE, $context);
        $single_feed = simplexml_load_string((string) $file);
        $feed[] = $single_feed;
        $items = [];
        $count = 0;
        if (count($feed) > 0) {
            foreach ($feed as $data) {
                if (!empty($data)) {
                    if (isset($data->channel->item)) {
                        $items_count = count($data->channel->item);
                        foreach ($data->channel->item as $item) {
                            $items_count--;
                            $item = $data->channel->item[$items_count];
                            if ($count >= $max) {
                                break;
                            }
                            $info = $this->dom->get_info($item->link, $this->data["mode"]);
                            $items[] = [
                                "link" => $item->link,
                                "title" => $info["title"],
                                "image" => $info["image"],
                            ];
                        }
                    } else {
                        $response = array(
                            'success' => false,
                            'message' => 'Your provided link has not valid RSS feed, Please fix and try again'
                        );
                    }
                } else {
                    $response = array(
                        'success' => false,
                        'messazge' => 'Your provided link has not valid RSS feed, Please fix and try again'
                    );
                }
            }
            // Set the flag to true after the foreach loop
            $response = array(
                'success' => true,
                'data' => $items
            );
        } else {
            $response = array(
                'success' => false,
                'message' => 'Your provided link has not valid RSS feed, Please fix and try again.'
            );
        }
        return $response;
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
}
