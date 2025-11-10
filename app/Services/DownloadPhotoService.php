<?php

namespace App\Services;

use DOMXPath;
use Exception;
use DOMDocument;

class DownloadPhotoService
{
    private $dom;
    public function __construct()
    {
        $this->dom = new HtmlParseService();
    }
    public function fetch($data)
    {
        try {
            info('service');
            $is_pinterest = $data["mode"] == "pinterst" ? true : false;
            $image = $this->fetchThumbnail($data["url"], $is_pinterest);
            $response = [
                "success" => true,
                "data" => $image
            ];
        } catch (Exception $e) {
            $repsonse = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return $repsonse;
    }
    private function fetchThumbnail(string $url, bool $is_pinterest = false): ?string
    {
        // 1. Pinterest Dimension Targets (Height x Width)
        // Note: The arrays must be mapped by index to form the correct pairs.
        $heightArray = ["1128", "900", "1000", "1024", "1349"];
        $widthArray = ["564", "700", "1500", "512", "759"];

        $pinterest_targets = [];
        for ($i = 0; $i < count($heightArray); $i++) {
            // Store targets as strings for easy comparison: "H x W"
            $pinterest_targets[] = "{$heightArray[$i]}x{$widthArray[$i]}";
        }

        // 2. Fetch HTML Content
        // Using file_get_contents is simple, but in a real-world/Laravel app, use Guzzle for robust error handling.
        // We add context to prevent SSL errors on some servers.
        $context = stream_context_create([
            "http" => ["header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)"],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            // Failed to fetch the URL content
            return null;
        }

        // 3. Parse HTML using DOMDocument
        $dom = new DOMDocument();
        // Suppress error warnings for malformed HTML common on the web
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $found_image_url = null;

        // --- STRATEGY 1: Check Open Graph/Twitter Meta Tags (Highest Priority for Thumbnails) ---

        // Query for standard Open Graph image tags
        $meta_image_query = '//meta[@property="og:image" or @name="twitter:image" or @name="image"]';
        $meta_nodes = $xpath->query($meta_image_query);

        foreach ($meta_nodes as $node) {
            $src = $node->getAttribute('content');

            if ($is_pinterest) {
                // Check for Open Graph dimensions if Pinterest mode is active
                $height = $xpath->query('//meta[@property="og:image:height"]')->item(0)?->getAttribute('content');
                $width = $xpath->query('//meta[@property="og:image:width"]')->item(0)?->getAttribute('content');

                if ($height && $width) {
                    $current_dim = "{$height}x{$width}";
                    if (in_array($current_dim, $pinterest_targets)) {
                        // Match found in meta tags!
                        return $src;
                    }
                }
            } else {
                // If not Pinterest mode, the first meta image is usually the thumbnail.
                return $src;
            }
        }


        // --- STRATEGY 2: Check all <img> tags (Fallback and Pinterest dimension check) ---

        $img_nodes = $xpath->query('//img');

        foreach ($img_nodes as $node) {
            $src = $node->getAttribute('src');

            // Ensure the source URL is valid and complete
            if (empty($src)) {
                continue;
            }

            // Complete relative URLs (basic check, more robust logic might be needed)
            if (strpos($src, '//') === false && strpos($src, '/') === 0) {
                $parsed_url = parse_url($url);
                $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                $src = $base_url . $src;
            } elseif (strpos($src, '//') === false) {
                // Skip relative paths like 'images/foo.jpg' if we can't determine the base
                continue;
            }

            if ($is_pinterest) {
                // Check dimensions defined in HTML attributes
                $height_attr = $node->getAttribute('height');
                $width_attr = $node->getAttribute('width');

                if ($height_attr && $width_attr) {
                    $current_dim = "{$height_attr}x{$width_attr}";

                    if (in_array($current_dim, $pinterest_targets)) {
                        // Match found in <img> tag attributes!
                        return $src;
                    }
                }
                // Note: If no height/width attributes are set, we cannot fulfill the Pinterest requirement.
            } else {
                // Not Pinterest mode, return the first reasonably large image found
                // A simple heuristic: ensure the image URL seems plausible (not a tiny icon)
                if (strpos($src, '.png') !== false || strpos($src, '.jpg') !== false) {
                    return $src;
                }
            }
        }

        // If we reached here, no suitable image was found.
        return null;
    }
}
