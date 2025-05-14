<?php

namespace App\Services;

use DOMDocument;
use SimpleXMLElement;
use App\Services\HttpService;
use voku\helper\HtmlDomParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class HtmlParseService
{
    private $client;
    private $dom;
    // private $parser;
    public function __construct()
    {
        $this->client = new HttpService();
        $this->dom = new DOMDocument();
        // $this->parser = new HtmlDomParser();
    }
    public function get_info($url, $mode = null)
    {
        $response = $this->client->get($url, [], ['User-Agent' => $this->user_agent()]);
        if ($response["status"] == "200") {
            if ($mode == 1) {
                $pinterest = true;
            } else { //for pinterest images
                $pinterest = false;
            }
            $response = $response["body"];
            @$this->dom->loadHTML($response);
            $tags = $this->dom->getElementsByTagName('img');
            $html = HtmlDomParser::str_get_html($response);
            // Post title
            $title = $html->find("meta[property='og:title']", 0)->content ? $html->find("meta[property='og:title']", 0)->content : $html->find("meta[name='twitter:title']", 0)->content;
            if (empty($title)) {
                $title = $this->dom->getElementsByTagName('title')->item(0);
                $title = empty($title) ? $html->find('title', 0) : $title->nodeValue;
                $colon_pos = strpos($title, needle: ":");
                if ($colon_pos !== false) {
                    $title = substr($title, 0, $colon_pos);
                }
            }
            // Post image
            $image = $html->find("meta[property='og:image']", 0)->content ? $html->find("meta[property='og:image']", 0)->content : $html->find("meta[name='twitter:image']", 0)->content;
            if ($pinterest) {
                $pinterest_image = $this->fetch_pinterest_image($tags);
                if (!empty($pinterest_image)) {
                    $image = $pinterest_image;
                }
            }
            if (empty($image)) {
                $json_ld = $this->get_string_between($response, '<script type="application/ld+json" class="yoast-schema-graph">', "</script>");
                if ($json_ld) {
                    $data = json_decode($json_ld, true);
                    if ($data) {
                        if (isset($data['@graph'])) {
                            if (isset($data['@graph'][0]['thumbnailUrl'])) {
                                $image = $data['@graph'][0]['thumbnailUrl'];
                            }
                        }
                    }
                }
            }
            if (empty($image)) {
                $thumbnails = $this->dom->getElementsByTagName('img');
                foreach ($thumbnails as $thumbnail) {
                    if (str_contains($thumbnail->getAttribute('class'), 'pin')) {
                        $image = !empty($thumbnail->getAttribute('data-lazy-src')) ? $thumbnail->getAttribute('data-lazy-src') : $thumbnail->getAttribute('src');
                    }
                    if (empty($image)) {
                        if (str_contains($thumbnail->getAttribute('class'), 'thumbnail')) {
                            $image = !empty($thumbnail->getAttribute('data-lazy-src')) ? $thumbnail->getAttribute('data-lazy-src') : $thumbnail->getAttribute('src');
                        }
                        if (str_contains($thumbnail->getAttribute('class'), 'featured')) {
                            $image = !empty($thumbnail->getAttribute('data-lazy-src')) ? $thumbnail->getAttribute('data-lazy-src') : $thumbnail->getAttribute('src');
                        }
                    }
                }
            }
            if (empty($image)) {
                $metaTags = $this->dom->getElementsByTagName('meta');
                foreach ($metaTags as $meta) {
                    if ($meta->getAttribute('property') == 'og:image') {
                        $image = $meta->getAttribute('content');
                    }
                    if ($meta->getAttribute('property') == 'og:image:secure_url') {
                        $image = $meta->getAttribute('content');
                    }
                    if (empty($image) && $meta->getAttribute('name') == 'twitter:image') {
                        $image = $meta->getAttribute('content');
                    }
                }
            }
            if (empty($image)) {
                $thumbnails = $this->dom->getElementsByTagName('div');
                foreach ($thumbnails as $thumbnail) {
                    if (str_contains($thumbnail->getAttribute('class'), 'thumbnail')) {
                        $image = $thumbnail->getElementsByTagName('img')->item(0);
                        $image = $image->getAttribute('src');
                    }
                    if (str_contains($thumbnail->getAttribute('class'), 'featured')) {
                        $image = $thumbnail->getElementsByTagName('img')->item(0);
                        $image = $image->getAttribute('src');
                    }
                }
            }
            return array(
                "title" => $title,
                "image" => $image
            );
        } else {
            return false;
        }
    }

    public function fix($post)
    {
        $type = $post->type;
        if ($type == 'pinterest') {
            $mode = 1;
        } else {
            $mode = 0;
        }
        $info = $this->get_info($post->url, $mode);
        return $info;
    }

    private function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0)
            return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    private function fetch_pinterest_image($tags)
    {
        $image = '';
        $pin_image = false;
        $pinterest_image = '';
        foreach ($tags as $tag) {
            if ($this->get_aspect_ratio($tag)) {
                $pin_image = true;
                $pinterest_image = $tag->hasAttribute('data-lazy-src') ? $tag->getAttribute("data-lazy-src") : $tag->getAttribute('src');
                break;
            }
        }
        if ($pin_image) {
            $image = $pinterest_image;
        }
        return $image;
    }

    private function get_aspect_ratio($image)
    {
        $pin_image = false;
        if (!empty($image)) {
            $height = $image->getAttribute('height') ? (float) $image->getAttribute('height') : 0;
            $width = $image->getAttribute('width') ? (float) $image->getAttribute('width') : 0;
            if (empty($height) || empty($width)) {
                $dimensions = $this->getImageDimensionsFromUrl($image->getAttribute('src'));
                if (isset($dimensions["width"])) {
                    $width = $dimensions["width"];
                }
                if (isset($dimensions["height"])) {
                    $width = $dimensions["height"];
                }
            }
            $heightArray = array("1128", "900", "1000", "1024", "1349");
            $widthArray = array("564", "700", "1500", "512", "759");
            if (in_array(ceil($height), $heightArray) && in_array(ceil($width), $widthArray)) {
                $pin_image = true;
            }
        }
        return $pin_image;
    }

    private function getImageDimensionsFromUrl($url)
    {
        $ch = curl_init($url);
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
        curl_setopt($ch, CURLOPT_HEADER, false); // Don't include the header in the output
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"); // Set a common User-Agent
        curl_setopt($ch, CURLOPT_REFERER, $url); // Set Referer to the image URL itself or a relevant page
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Fail silently on HTTP errors (like 400 or 500 series)
        // We only need a small portion to get dimensions, but getimagesize on a stream needs enough header info.
        // Fetching the whole body is often necessary for getimagesize to work with the downloaded data.
        // A better approach is to use getimagesize on a temporary local file after downloading.
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            // cURL error
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => "cURL error: $error"];
        }
        curl_close($ch);
        if ($http_code >= 400) {
            // HTTP error (like 403 or 406)
            return ['error' => "HTTP error: $http_code"];
        }

        // Use getimagesize on the downloaded content
        // We need to save the content to a temporary file or use data streams
        // Using a data stream is generally cleaner

        $image_data = $response;
        $uri = 'data://application/octet-stream;base64,' . base64_encode($image_data);
        $image_info = getimagesize($uri);
        if ($image_info === false) {
            return ['error' => 'Could not get image size from downloaded data.'];
        }

        return [
            'width' => $image_info[0],
            'height' => $image_info[1],
        ];
    }

    public function user_agent()
    {
        $agent[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:53.0) Gecko/20100101 Firefox/53.0";
        $agent[] = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0; Trident/5.0)";
        $agent[] = "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0; MDDCJS)";
        $agent[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393";
        $agent[] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
        $agent[] = "Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3B48b Safari/419.3";
        $agent[] = "Mozilla/5.0 (iPhone14,6; U; CPU iPhone OS 15_4 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/19E241 Safari/602.1";
        $agent[] = "Mozilla/5.0 (iPhone14,3; U; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/19A346 Safari/602.1";
        $agent[] = "Mozilla/5.0 (iPhone13,2; U; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1";
        $agent[] = "Mozilla/5.0 (iPhone12,1; U; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1";
        $agent[] = "Mozilla/5.0 (iPhone12,1; U; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1";
        $agent[] = "Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1";
        $agent[] = "Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/69.0.3497.105 Mobile/15E148 Safari/605.1";
        $agent[] = "Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/13.2b11866 Mobile/16A366 Safari/605.1.15";
        $agent[] = "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1";
        $agent[] = "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1";
        $agent[] = "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A5370a Safari/604.1";
        $agent[] = "Mozilla/5.0 (iPhone9,3; U; CPU iPhone OS 10_0_1 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/14A403 Safari/602.1";
        $agent[] = "Mozilla/5.0 (iPhone9,4; U; CPU iPhone OS 10_0_1 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/14A403 Safari/602.1";
        $agent[] = "Mozilla/5.0 (Apple-iPhone7C2/1202.466; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543 Safari/419.3";
        $agent[] = "Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; RM-1152) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36 Edge/15.15254";
        $agent[] = "Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; RM-1127_16056) AppleWebKit/537.36(KHTML, like Gecko) Chrome/42.0.2311.135 Mobile Safari/537.36 Edge/12.10536";
        $agent[] = "Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.1058";
        $agent[] = "Mozilla/5.0 (Linux; Android 12; SM-X906C Build/QP1A.190711.020; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/80.0.3987.119 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 11; Lenovo YT-J706X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 6.0.1; SGP771 Build/32.2.A.0.253; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 6.0.1; SHIELD Tablet K1 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 7.0; SM-T827R4 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.116 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 5.0.2; SAMSUNG SM-T550 Build/LRX22G) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/3.3 Chrome/38.0.2125.102 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 4.4.3; KFTHWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/47.1.79 like Chrome/47.0.2526.80 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 5.0.2; LG-V410/V41020c Build/LRX22G) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/34.0.1847.118 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246";
        $agent[] = "Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36";
        $agent[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9";
        $agent[] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36";
        $agent[] = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1";
        $agent[] = "Mozilla/5.0 (Linux; Android 12; SM-S906N Build/QP1A.190711.020; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/80.0.3987.119 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 10; SM-G996U Build/QP1A.190711.020; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 10; SM-G980F Build/QP1A.190711.020; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/78.0.3904.96 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 9; SM-G973U Build/PPR1.180610.011) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 7.0; SM-G892A Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/60.0.3112.107 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 7.0; SM-G930VC Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/58.0.3029.83 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 6.0.1; SM-G935S Build/MMB29K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 6.0.1; SM-G920V Build/MMB29K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 5.1.1; SM-G928X Build/LMY47X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.83 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 12; Pixel 6 Build/SD1A.210817.023; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/94.0.4606.71 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 11; Pixel 5 Build/RQ3A.210805.001.A1; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/92.0.4515.159 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 10; Google Pixel 4 Build/QD1A.190821.014.C2; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/78.0.3904.108 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 10; Google Pixel 4 Build/QD1A.190821.014.C2; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/78.0.3904.108 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 8.0.0; Pixel 2 Build/OPD1.170811.002; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/59.0.3071.125 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 7.1.1; Google Pixel Build/NMF26F; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/54.0.2840.85 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 6.0.1; Nexus 6P Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.83 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 9; J8110 Build/55.0.A.0.552; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/71.0.3578.99 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 7.1.1; G8231 Build/41.2.A.0.219; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/59.0.3071.125 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 6.0.1; E6653 Build/32.2.A.0.253) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 10; HTC Desire 21 pro 5G) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.127 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 10; Wildfire U20 5G) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.136 Mobile Safari/537.36";
        $agent[] = "Mozilla/5.0 (Linux; Android 6.0; HTC One X10 Build/MRA58K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/61.0.3163.98 Mobile Safari/537.36";
        return $agent[RAND(0, 60)];
    }

    /**
     * Fetch RSS feed and extract data including images.
     *
     * @param string $feedUrl The URL of the RSS feed.
     * @return array An array of feed items with title, link, and image.
     */
    public function fetchFeed(string $feedUrl): array
    {
        $items = [];

        try {
            // Fetch the RSS feed content
            $response = Http::get($feedUrl);

            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());

                if ($xml === false) {
                    Log::error("Failed to parse RSS feed XML from: " . $feedUrl);
                    return [];
                }

                // Determine if the feed is likely from Pinterest based on URL or other indicators
                $isPinterestFeed = str_contains(strtolower($feedUrl), 'pinterest.com'); // Basic check

                // Define preferred Pinterest image dimensions
                $pinterestPreferredHeights = ["1128", "900", "1000", "1024", "1349"];
                $pinterestPreferredWidths = ["564", "700", "1500", "512", "759"];

                foreach ($xml->channel->item as $item) {
                    $title = (string) $item->title;
                    $link = (string) $item->link;
                    $imageUrl = null;

                    // --- Image Extraction Logic ---

                    if ($isPinterestFeed) {
                        // Logic for Pinterest feeds
                        $imageUrl = $this->findPinterestImage($item, $pinterestPreferredWidths, $pinterestPreferredHeights);

                        // If no preferred Pinterest image found, try finding a thumbnail
                        if (!$imageUrl) {
                            $imageUrl = $this->findThumbnail($item);
                        }
                    } else {
                        // Logic for non-Pinterest feeds - look for common image tags
                        $imageUrl = $this->findGenericImage($item);
                    }

                    // If no image found at all, maybe look in description/content (more complex parsing needed)
                    if (!$imageUrl) {
                        $imageUrl = $this->findImageInContent($item);
                    }


                    $items[] = [
                        'title' => $title,
                        'link' => $link,
                        'image' => $imageUrl,
                    ];
                }
            } else {
                Log::error("Failed to fetch RSS feed from: " . $feedUrl . " Status: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Error fetching or parsing RSS feed: " . $e->getMessage());
        }

        dd($items);
    }

    /**
     * Find a Pinterest image with preferred dimensions.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @param array $preferredWidths Array of preferred widths.
     * @param array $preferredHeights Array of preferred heights.
     * @return string|null The image URL if found, otherwise null.
     */
    protected function findPinterestImage(SimpleXMLElement $item, array $preferredWidths, array $preferredHeights): ?string
    {
        // Pinterest often uses media:content or includes images in description/content
        // We'll prioritize media:content with dimensions if available

        // Check media:content
        if (isset($item->children('media', true)->content)) {
            foreach ($item->children('media', true)->content as $content) {
                $attributes = $content->attributes();
                if (isset($attributes['url']) && isset($attributes['width']) && isset($attributes['height'])) {
                    $url = (string) $attributes['url'];
                    $width = (string) $attributes['width'];
                    $height = (string) $attributes['height'];

                    if (in_array($width, $preferredWidths) && in_array($height, $preferredHeights)) {
                        return $url; // Found a preferred size image
                    }
                }
            }
        }

        // If no preferred size image in media:content, check other potential image locations
        // (You might need to add more specific logic here based on actual Pinterest feed structure)

        return null; // No preferred Pinterest image found
    }

    /**
     * Find a thumbnail image.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @return string|null The thumbnail URL if found, otherwise null.
     */
    protected function findThumbnail(SimpleXMLElement $item): ?string
    {
        // Check media:thumbnail
        if (isset($item->children('media', true)->thumbnail)) {
            $attributes = $item->children('media', true)->thumbnail->attributes();
            if (isset($attributes['url'])) {
                return (string) $attributes['url'];
            }
        }

        // Check enclosure
        if (isset($item->enclosure)) {
            $attributes = $item->enclosure->attributes();
            if (isset($attributes['url']) && str_starts_with($attributes['type'], 'image/')) {
                return (string) $attributes['url'];
            }
        }


        return null; // No thumbnail found
    }


    /**
     * Find a generic image in common RSS tags.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @return string|null The image URL if found, otherwise null.
     */
    protected function findGenericImage(SimpleXMLElement $item): ?string
    {
        // Check enclosure
        if (isset($item->enclosure)) {
            $attributes = $item->enclosure->attributes();
            if (isset($attributes['url']) && str_starts_with($attributes['type'], 'image/')) {
                return (string) $attributes['url'];
            }
        }

        // Check media:content (common for images)
        if (isset($item->children('media', true)->content)) {
            foreach ($item->children('media', true)->content as $content) {
                $attributes = $content->attributes();
                if (isset($attributes['url']) && str_starts_with($attributes['type'], 'image/')) {
                    return (string) $attributes['url'];
                }
            }
        }

        // Add more checks for other common image tags if needed (e.g., <image>)

        return null; // No generic image found in common tags
    }

    /**
     * Find an image within the item's description or content.
     * This requires parsing HTML content within the XML.
     *
     * @param SimpleXMLElement $item The RSS item element.
     * @return string|null The image URL if found, otherwise null.
     */
    protected function findImageInContent(SimpleXMLElement $item): ?string
    {
        $content = null;

        // Check content:encoded (often contains full HTML)
        if (isset($item->children('content', true)->encoded)) {
            $content = (string) $item->children('content', true)->encoded;
        }
        // Fallback to description if content:encoded is not available
        else if (isset($item->description)) {
            $content = (string) $item->description;
        }

        if ($content) {
            // Use regular expressions or a DOM parser to find <img> tags
            // Using regex is simpler but less robust than a DOM parser
            if (preg_match('/<img.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
                return $matches[1]; // Return the first image src found
            }
        }

        return null; // No image found in content/description
    }


    /**
     * Helper function to get image dimensions from a URL.
     * Note: This requires fetching image data and can be slow.
     * Consider if this is necessary or if dimensions can be extracted from the feed itself.
     *
     * @param string $imageUrl The URL of the image.
     * @return array|false An array [width, height] or false on failure.
     */
    protected function getImageDimensions(string $imageUrl): array|false
    {
        // Use getimagesize to get dimensions. This fetches part of the image.
        // Be cautious with performance if calling this for many images.
        // Ensure the URL is accessible and getimagesize is enabled in PHP config.
        $dimensions = @getimagesize($imageUrl);

        if ($dimensions) {
            return ['width' => $dimensions[0], 'height' => $dimensions[1]];
        }

        return false;
    }
}
