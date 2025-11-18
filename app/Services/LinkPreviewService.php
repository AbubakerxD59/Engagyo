<?php
// app/Services/LinkPreviewService.php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class LinkPreviewService
{
    /**
     * The User-Agent string to mimic a known social media crawler (like Publer/Facebook).
     */
    protected const USER_AGENT = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';

    /**
     * Fetches and parses a URL to generate a link preview object.
     *
     * @param string $url The URL to fetch.
     * @return array The link preview data.
     */
    public function get_link_valid_meta($url)
    {
        $response = array();
        $info = array();
        $getmetainfo = new HtmlParseService();
        $info = $getmetainfo->get_info($url);
        try {
            if (!empty($info) && isset($info['title']) && isset($info['image'])) {
                $response['image'] = $info['image'];
                $entityReplacements = array(
                    "&#039;" => "'",
                    "&#39;" => "'",
                    "039" => "'",
                    "&#8211;" => "-",
                    "&#8212;" => "--",
                );
                $response['title'] = str_replace(array_keys($entityReplacements), array_values($entityReplacements), $info['title']);
                $response['description'] = isset($info['description']) ? $info['description'] : '';
                $response['description'] = isset($info['description']) ? str_replace(array_keys($entityReplacements), array_values($entityReplacements), $info['description']) : '';
                $response['status'] = true;
            } else {
                $response['status'] = false;
                $response['error'] = $info['message'];
            }
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = $e->getMessage();
        }
        return $response;
    }
}
