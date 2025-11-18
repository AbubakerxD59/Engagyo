<?php

namespace App\Services;

use Exception;
use DOMDocument;
use App\Services\HttpService;
use voku\helper\HtmlDomParser;

class HtmlParseService
{
    private $client;
    private $pinterest_active = 0;
    private $dom;
    private $downloadPhoto;
    // private $parser;
    public function __construct($pinterest_active)
    {
        $this->pinterest_active = $pinterest_active;
        $this->client = new HttpService();
        $this->dom = new DOMDocument();
        $this->downloadPhoto = new DownloadPhotoService();
    }
    public function get_info($url, $fetchPhoto = 0)
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)",
                // CURLOPT_USERAGENT => $this->user_agent(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache"
                ),
            ));

            $json_response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $response = [];
                $dom = new DOMDocument();
                @$dom->loadHTML($json_response);
                $html = HtmlDomParser::str_get_html($json_response);
                $meta_title = $html->find("meta[property='og:title']", 0)->content;
                if (empty($meta_title)) {
                    $meta_title = $html->find("meta[name='twitter:title']", 0)->content;
                }
                if (empty($meta_title)) {
                    $title = $dom->getElementsByTagName('title');
                    if ($title->length > 0) {
                        $page_title = $title->item(0)->textContent;
                        $meta_title = $page_title;
                    }
                }
                if ($fetchPhoto) {
                    $meta_image = $this->fetchPhoto($json_response);
                    $response['image'] = $meta_image;
                }
                $response['status'] = true;
                $response['title'] = !empty($meta_title) ? html_entity_decode($meta_title) : $meta_title;
                $response['link'] = $url;
            }
        } catch (Exception $e) {
            $response = [
                "status" => false,
                "message" => $e->getMessage()
            ];
        }
        return $response;
    }

    public function fetchPhoto($response)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($response);
        // fetch from meta tags
        $meta_image = null;
        $metaTags = $dom->getElementsByTagName('meta');
        $ogimage = $ogimagesecure = null;
        foreach ($metaTags as $tag) {
            if ($tag->hasAttribute('property') && $tag->getAttribute('property') === 'og:image') {
                if ($tag->hasAttribute('content')) {
                    $ogimage = $tag->getAttribute('content');
                }
            }
            if ($tag->hasAttribute('property') && $tag->getAttribute('property') === 'og:image:secure_url') {
                if ($tag->hasAttribute('content')) {
                    $ogimagesecure = $tag->getAttribute('content');
                }
            }
        }
        if ($ogimage && $ogimagesecure && $ogimage == $ogimagesecure) {
            $meta_image = $ogimage;
        } elseif ($ogimagesecure) {
            $meta_image = $ogimagesecure;
        } else {
            $meta_image = $ogimage;
        }
        $images = $dom->getElementsByTagName('img');
        // fetch images if pinterest channel is active 
        if ($this->pinterest_active) {
            $pinterest_image = $this->fetch_pinterest_image($images);
            if (!empty($pinterest_image)) {
                $meta_image = $pinterest_image;
            }
        }
        // fetch images from img tags
        if (empty($meta_image)) {
            foreach ($images as $thumbnail) {
                if ($this->check_for($thumbnail->getAttribute('class'), 'post-thumbnail post-image')) {
                    $image = $thumbnail->hasAttribute('data-lazy-src') ? $thumbnail->getAttribute('data-lazy-src') : $thumbnail->getAttribute('src');
                    $meta_image = $image;
                }
            }
        }
        // fetch images inside divs
        if (empty($meta_image)) {
            $thumbnails = $dom->getElementsByTagName('div');
            foreach ($thumbnails as $thumbnail) {
                if ($this->check_for($thumbnail->getAttribute('class'), 'featured thumbnail post-image')) {
                    $image = $thumbnail->getElementsByTagName('img')->item(0);
                    $src = $image->getAttribute('src');
                    $meta_image = $src;
                }
            }
        }
        return $meta_image;
    }

    public function check_for($source, $find)
    {
        $words = explode(' ', $find);
        foreach ($words as $string) {
            if (str_contains($source, $string)) {
                return true;
            }
        }
        return false;
    }

    public function fix($post)
    {
        $type = $post->type;
        if ($type == 'pinterest') {
            $mode = 1;
        } else {
            $mode = 0;
        }
        $info = [];
        $title = $this->get_info($post->url, $mode);
        $image = $this->downloadPhoto->fetchThumbnail($post->url, $post->is_pinterest);
        $info = [
            "title" => isset($title["title"]) ? $title["title"] : "",
            "image" => $image,
        ];
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
        $pinterest_image = '';
        foreach ($tags as $tag) {
            if ($this->get_aspect_ratio($tag)) {
                $pinterest_image = $tag->hasAttribute('data-lazy-src') ? $tag->getAttribute("data-lazy-src") : $tag->getAttribute('src');
                return $pinterest_image;
            }
        }
        return null;
    }

    private function get_aspect_ratio($image)
    {
        $pin_image = false;
        if (!empty($image)) {
            $height = $image->getAttribute('height') ? (float) $image->getAttribute('height') : 0;
            $width = $image->getAttribute('width') ? (float) $image->getAttribute('width') : 0;
            if (empty($height) || empty($width)) {
                $dimensions = getimagesize($image->getAttribute('src'));
                if (isset($dimensions["width"])) {
                    $width = $dimensions["width"];
                }
                if (isset($dimensions["height"])) {
                    $width = $dimensions["height"];
                }
            }
            $widthArray = array("564", "700", "1500", "512", "759", "640");
            $heightArray = array("1128", "900", "1000", "1024", "1349", "960");
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
        $agent[] = "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)";
        $agent[] = "facebookexternalhit/1.1";
        $agent[] = "facebookcatalog/1.0";
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
}
