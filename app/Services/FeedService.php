<?php

namespace App\Services;

use Exception;
use App\Models\Post;
use App\Services\SitemapService;
use Illuminate\Support\Facades\Http;
use Vedmant\FeedReader\Facades\FeedReader;
use Illuminate\Http\Client\RequestException;

class FeedService
{
    /**
     * @var string The user agent to use for cURL requests.
     */
    private const USER_AGENT = 'RssFeedService/1.0 (PHP cURL)';

    /**
     * @var int The maximum number of attempts to fetch the feed.
     */
    private const MAX_RETRIES = 3;

    /**
     * @var int The delay in seconds before retrying a failed fetch attempt.
     */
    private const RETRY_DELAY_SECONDS = 10;
    private $post;
    private $dom;
    private $data;
    private $body;
    private $sitemap;
    private $heightArray = [];
    private $widthArray = [];
    public function __construct($data)
    {
        $this->post = new Post();
        $this->dom = new HtmlParseService();
        $this->sitemap = new SitemapService();
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
        $normalizedUrl = $this->normalizeUrl($websiteUrl);
        if ($this->data["exist"]) {
            $feedUrls = $this->sitemap->fetchArticles($websiteUrl);
        } else {
            $feedUrls = $this->fetchRss($normalizedUrl);
        }
        if ($feedUrls["success"]) {
            try {
                $items = $feedUrls["data"];
                if (count($items) > 0) {
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
                    create_notification($this->data["user_id"], $this->body, "Automation");
                    return array(
                        "success" => false,
                        "message" =>  $this->body["message"]
                    );
                }
            } catch (Exception $e) {
                $this->body["message"] = $e->getMessage();
                create_notification($this->data["user_id"], $this->body, "Automation");
                return array(
                    "success" => false,
                    "message" =>  $this->body["message"]
                );
            }
        } else {
            $this->body["message"] = $feedUrls["message"];
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
            $xmlContent = $this->fetchContent($targetUrl, self::MAX_RETRIES);
            $parseFeed = $this->parseFeed($xmlContent);
            if ($parseFeed['success']) {
                $response = [
                    "success" => true,
                    "data" => $parseFeed['data']
                ];
            } else {
                $response = [
                    "success" => false,
                    "message" => $parseFeed['message']
                ];
            }
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }

        // try {
        //     $agent = $this->dom->user_agent();
        //     $options = ['curl_options' => [CURLOPT_USERAGENT => $agent]];
        //     $url = $targetUrl;
        //     $feed = FeedReader::read($url, "default", $options);
        //     $items = array_slice($feed->get_items(), 0, $max);
        //     $posts = [];
        //     foreach ($items as $item) {
        //         $title = $item->get_title();
        //         $link = $item->get_link();
        //         $posts[] = [
        //             'title' => $title,
        //             'link' => $link,
        //         ];
        //     }
        //     $response = [
        //         "success" => true,
        //         "data" => $posts
        //     ];
        // } catch (Exception $e) {
        //     $response = [
        //         "success" => false,
        //         "message" => $e->getMessage()
        //     ];
        // }

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

    /**
     * Normalizes the URL by appending 'feed' or replacing 'rss' with 'feed'.
     *
     * @param string $url The input URL.
     * @return string The normalized URL.
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        // Remove scheme if present, to normalize paths better later
        $base = preg_replace('/^https?:\/\//i', '', $url);

        // 1. Replace trailing "rss" with "feed" (case-insensitive)
        if (preg_match('/rss$/i', $base)) {
            $base = substr($base, 0, -3) . 'feed';
        }

        // 2. Append "/feed" if the URL doesn't already look like a feed path.
        // We check if it ends with /feed or any common feed path component
        if (!preg_match('/(\/feed|\.xml|\.rss|\.atom)$/i', $base)) {
            // Ensure no double slashes if the base URL already ended with one
            $url = rtrim($url, '/') . '/feed';
        }

        return $url;
    }

    /**
     * Fetches the content from the URL with a retry mechanism.
     *
     * @param string $url The full URL to fetch.
     * @param int $maxRetries The maximum number of attempts.
     * @return string The XML content of the feed.
     * @throws \Exception If fetching fails after all retries.
     */
    private function fetchContent(string $url, int $maxRetries): string
    {
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
            curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Set a timeout

            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($content !== false && $httpCode >= 200 && $httpCode < 300) {
                return $content;
            }

            if ($attempt < $maxRetries) {
                $delay = self::RETRY_DELAY_SECONDS + ($attempt - 1) * 5; // Add increasing delay
                sleep($delay);
            }
        }

        throw new \Exception("Failed to fetch feed content after {$maxRetries} attempts.");
    }

    /**
     * Parses the XML content and extracts valid titles.
     * Supports both RSS (channel/item) and Atom (feed/entry) formats.
     *
     * @param string $xmlContent The raw XML feed content.
     * @return array An array of filtered article titles.
     */
    private function parseFeed(string $xmlContent): array
    {
        try {
            $xml = new \SimpleXMLElement($xmlContent);
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Failed to parse XML content: " . $e->getMessage()
            ];
        }
        $titles = [];
        // Check for RSS format (channel/item)
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $title = (string)$item->title;
                // RSS link is usually a direct child tag
                $link = (string)$item->link;

                if ($this->isValidTitle($title)) {
                    $articles[] = [
                        'title' => $title,
                        'link' => $link
                    ];
                }
            }
        }
        // Check for Atom format (feed/entry)
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $title = (string)$entry->title;
                $link = '';

                // Atom links are often stored as attributes on <link> tags. We look for rel="alternate".
                if (isset($entry->link)) {
                    foreach ($entry->link as $linkElement) {
                        $rel = (string)$linkElement->attributes()->rel;
                        $href = (string)$linkElement->attributes()->href;

                        // Prefer the canonical "alternate" link for the article URL
                        if ($rel === 'alternate' && $href) {
                            $link = $href;
                            break;
                        }
                    }
                    // Fallback: If no alternate found, use the first link's href attribute or inner content
                    if (empty($link) && isset($entry->link[0])) {
                        $firstLink = $entry->link[0];
                        $link = isset($firstLink->attributes()->href) ? (string)$firstLink->attributes()->href : (string)$firstLink;
                    }
                }

                if ($this->isValidTitle($title)) {
                    $articles[] = [
                        'title' => $title,
                        'link' => $link
                    ];
                }
            }
        }
        return [
            'success' => true,
            'data' => $articles
        ];
    }

    /**
     * Checks if a title is valid (non-empty and not system/admin content).
     *
     * @param string $title The title to validate.
     * @return bool True if the title is valid, false otherwise.
     */
    private function isValidTitle(string $title): bool
    {
        $title = trim($title);
        if (empty($title)) {
            return false;
        }

        $titleLower = strtolower($title);

        // Keywords that typically indicate non-article, system, or administrative content
        $invalidKeywords = [
            'bot verification',
            'admin dashboard',
            'login required',
            'system message',
            'comment moderation',
            'privacy policy',
            'terms of service',
            'site map',
            'error 404',
            'page not found',
        ];

        foreach ($invalidKeywords as $keyword) {
            if (str_contains($titleLower, $keyword)) {
                return false;
            }
        }

        // Filter out very short, likely non-descriptive titles
        if (strlen($title) < 10) {
            return false;
        }

        return true;
    }
}
