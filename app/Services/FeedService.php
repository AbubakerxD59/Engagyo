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
        info("data: " . json_encode($this->data));
        if ($this->data["exist"]) {
            $feedUrls = $this->fetchSitemap($websiteUrl);
            info("fetchSitemap: " . json_encode($feedUrls));
        } else {
            $feedUrls = $this->fetchRss($websiteUrl);
            info("fetchRss: " . json_encode($feedUrls));
        }
        if ($feedUrls["success"]) {
            info('success');
            try {
                $items = $feedUrls["data"];
                info('items: ' . json_encode($items));
                if (count($items) > 0) {
                    info('contains posts');
                    foreach ($items as $key => $item) {
                        $nextTime = $this->post->nextTime(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["source"], "type" => $this->data["type"]], $this->data["time"]);
                        $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["source"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $item["link"]])->first();
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
                } else {
                    $this->body["message"] = "Posts not Fetched!";
                    info(json_encode($this->body));
                    create_notification($this->data["user_id"], $this->body, "Automation");
                    return array(
                        "success" => false,
                        "message" =>  $this->body["message"]
                    );
                }
            } catch (Exception $e) {
                $this->body["message"] = $e->getMessage();
                info(json_encode($this->body));
                create_notification($this->data["user_id"], $this->body, "Automation");
                return array(
                    "success" => false,
                    "message" =>  $this->body["message"]
                );
            }
        } else {
            info('failed');
            $this->body["message"] = $feedUrls["message"];
            info(json_encode($this->body));
            create_notification($this->data["user_id"], $this->body, "Automation");
            return array(
                "success" => false,
                "message" =>  $this->body["message"]
            );
        }
    }

    private function fetchRss(string $targetUrl, int $max = 10)
    {
        try {
            $agent = $this->dom->user_agent();
            $options = ['curl_options' => [CURLOPT_USERAGENT => $agent]];
            $url = $targetUrl;
            $feed = FeedReader::read($url, "default", $options);
            $items = array_slice($feed->get_items(), 0, $max);
            info("items: " . json_encode($items));
            $posts = [];
            foreach ($items as $item) {
                $title = $item->get_title();
                $link = $item->get_link();
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
                                $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["source"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $url->loc])->first();
                                if (!$post) {
                                    $invalid_titles = [
                                        "bot verification",
                                        "admin"
                                    ];
                                    $rss = $this->dom->get_info($url->loc, $this->data["mode"]);
                                    if (isset($rss["title"])) {
                                        if (!in_array(strtolower($rss["title"]), $invalid_titles)) {
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
}
