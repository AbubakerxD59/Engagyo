<?php

namespace App\Services;

use Exception;
use App\Models\Post;
use SimpleXMLElement;

class FeedService
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
            "user_id" => isset($data["user_id"]) ? $data["user_id"] : '',
            "account_id" => isset($data["account_id"]) ? $data["account_id"] : '',
            "type" => isset($data["type"]) ? $data["type"] : '',
            "domain_id" => isset($data["domain_id"]) ? $data["domain_id"] : '',
            "url" => isset($data["url"]) ? $data["url"] : '',
        ];
    }
    public function fetch()
    {
        $websiteUrl = $this->data["url"];
        $feedUrls = $this->fetchRss($websiteUrl);
        if ($this->data["exist"]) {
            $feedUrls = $this->fetchSitemap($websiteUrl);
        } else {
            $feedUrls = $this->fetchRss($websiteUrl);
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

    private function appendFeedToUrl($url)
    {
        if (!strpos($url, 'feed') || !strpos($url, 'rss')) {
            if (substr($url, -5) !== '/feed') {
                $url .= '/feed';
            }
        }
        return strtolower($this->data["protocol"] . "://" . $url);
    }

    private function fetchRss(string $targetUrl, int $max = 10)
    {
        try {
            $posts = [];
            $links = $this->appendFeedToUrl($targetUrl);
            $userAgent = $this->dom->user_agent();
            $contextOptions = ['http' => ['user_agent' => $userAgent, 'ignore_errors' => true]];
            $context = stream_context_create($contextOptions);
            $file = file_get_contents($links, FALSE, $context);
            if (strpos($file, '<?xml') === false && strpos($file, '<rss') === false) {
                sleep(3);
                // 1. Initialize cURL session
                $ch = curl_init($links);
                // 2. Set options
                $headers = [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $cookieFile = 'cookies.txt';
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); // Store cookies
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); // Send cookies
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the content as a string instead of outputting it
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Important for handling redirects
                curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Set a timeout in seconds
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"); // A user agent can sometimes be required
                // 3. Execute the request
                $file = curl_exec($ch);
                // 5. Close the session
                curl_close($ch);
            }
            if ($file !== false) {
                $single_feed = simplexml_load_string((string) $file);
                if ($single_feed) {
                    $feed[] = $single_feed;
                    foreach ($feed as $data) {
                        if (!empty($data)) {
                            $i = 1;
                            if (isset($data->channel->item)) {
                                $items_count = count($data->channel->item);
                                foreach ($data->channel->item as $item) {
                                    $items_count--;
                                    $item = $data->channel->item[$items_count];
                                    if ($i > 10) {
                                        break;
                                    }
                                    $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["source"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $item->link])->first();
                                    if (!$post) {
                                        $posts[] = [
                                            "title" => $item->title,
                                            "link" => $item->link
                                        ];
                                        $i++;
                                    }
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
                                'message' => 'Your provided link has not valid RSS feed, Please fix and try again'
                            );
                        }
                    }
                    $response = array(
                        'success' => true,
                        'data' => $posts
                    );
                } else {
                    $response = array(
                        'success' => false,
                        'message' => 'Your provided link has not valid RSS feed, Please fix and try again.'
                    );
                }
            } else {
                $response = array(
                    'success' => false,
                    'message' => 'Your provided link has not valid RSS feed, Please fix and try again.'
                );
            }
        } catch (Exception $e) {
            $response = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
        return $response;
    }

    private function appendSitemapToUrl($url)
    {
        $url .= '/sitemap.xml';
        return strtolower($this->data["protocol"] . "://" . $url);
    }

    public function fetchSitemap(string $targetUrl, int $max = 10)
    {
        try {
            $posts = [];
            $http_domain = 'http://' . $targetUrl;
            $main_domain = strtolower($this->data["protocol"] . "://" . $targetUrl);
            $sitemapUrl = $this->appendSitemapToUrl($targetUrl);
            // context options
            $arrContextOptions = array('http' => ['method' => "GET", 'header' => "User-Agent: curl/7.68.0\r\n", 'ignore_errors' => true], "ssl" => array("verify_peer" => false, "verify_peer_name" => false));
            // load xml from sitemap.xml
            $xml = simplexml_load_file($sitemapUrl);
            if (!$xml) {
                $sitemapContent = file_get_contents($sitemapUrl, false, stream_context_create($arrContextOptions));
                if (strpos($xml, '<?xml') === false && strpos($xml, '<rss') === false) {
                    sleep(3);
                    // 1. Initialize cURL session
                    $ch = curl_init($sitemapUrl);
                    // 2. Set options
                    $headers = [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.5',
                        'Connection: keep-alive',
                        'Upgrade-Insecure-Requests: 1',
                    ];
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    $cookieFile = 'cookies.txt';
                    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); // Store cookies
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); // Send cookies
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the content as a string instead of outputting it
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Important for handling redirects
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Set a timeout in seconds
                    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"); // A user agent can sometimes be required
                    // 3. Execute the request
                    $sitemapContent = curl_exec($ch);
                    // 5. Close the session
                    curl_close($ch);
                }
                if (!empty($sitemapContent)) {
                    sleep(3);
                    $xml = simplexml_load_string($sitemapContent);
                }
            }
            if (count($xml) > 0) {
                $filteredSitemaps = [];
                foreach ($xml->sitemap as $sitemap) {
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
                $desiredPostCount = 10;
                $loc = (string) $selectedSitemap->loc;
                if (
                    strpos($loc, "post-sitemap") !== false ||
                    strpos($loc, "sitemap-post") !== false ||
                    strpos($loc, "sitemap-") !== false
                ) {
                    $sitemapUrl = $loc; // Use the filtered URL
                    sleep(3);
                    $sitemapXml = simplexml_load_file($sitemapUrl);
                    if (!$sitemapXml) {
                        sleep(3);
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
                    $postCount = 0;
                    foreach ($newSitemapXml->url as $url) {
                        if ($postCount >= $desiredPostCount) {
                            break;
                        }
                        $postUrl = (string) $url->loc; // Cast to string to get the URL 
                        if ($postUrl == $main_domain . '/' || $postUrl == $http_domain . '/') {
                            continue; // Skip the first iteration
                        }
                        // Check if the URL is already in the database
                        $post = $this->post->exist(["user_id" => $this->data["user_id"], "account_id" => $this->data["account_id"], "social_type" => $this->data["social_type"], "source" => $this->data["source"], "type" => $this->data["type"], "domain_id" => $this->data["domain_id"], "url" => $postUrl])->first();
                        $data = $this->dom->get_info($postUrl);
                        if (!$post) {
                            $posts[] = [
                                "title" => $data['title'],
                                "link" => $postUrl
                            ];
                        }
                    }
                    $response = [
                        'status' => true,
                        'message' => 'Good Work!! We are setting up your awesome feed, Please Wait.'
                    ];
                } else {
                    $response = [
                        'status' => false,
                        'error' => 'Sitemap Data not found!'
                    ];
                }
            } else {
                $response = [
                    'status' => false,
                    'error' => 'Failed to fetch the RSS feed'
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
}
