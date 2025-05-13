<?php

namespace App\Services;

use Feed;
use Exception;
use App\Models\Post;
use App\Models\Notification;
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
        $urlToCheck = rtrim($websiteUrl, '/') . '/feed';
        $discoveredUrls = '';
        try {
            $response = Http::get($urlToCheck);
            if ($response->successful()) {
                // Check content type if possible (more reliable)
                $discoveredUrls = $urlToCheck;
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
                        "message" => "Failed to fetch Feed from {$websiteUrl}"
                    ];
                }
            } else {
                $response = [
                    "success" => false,
                    "message" => "No response from Site RSS!"
                ];
            }
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => "No response from Site, please try again Later!"
            ];
        }
        return $response;
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
}
