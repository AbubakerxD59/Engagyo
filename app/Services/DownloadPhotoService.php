<?php

namespace App\Services;

use Exception;

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
            $repsonse = $this->dom->fetchPhoto($data["url"], $data["mode"]);
        } catch (Exception $e) {
            $repsonse = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return $repsonse;
    }
}
