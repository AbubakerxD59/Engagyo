<?php

namespace App\Http\Controllers;

use App\Services\HtmlParseService;
use App\Services\UrlMetaFetcher;
use DOMDocument;
use Illuminate\Http\Request;
use App\Services\FeedService;
use Illuminate\Support\Facades\Auth;

class GeneralController extends Controller
{
    public function previewLink(Request $request)
    {
        $user = Auth::user();
        $accounts = $user->getAccounts();
        $check = $accounts->where("type", "!=", "pinterest");
        $mode = count($check) > 0 ? 0 : 1;
        $link = $request->link;
        if (!empty($link)) {
            $service = new UrlMetaFetcher();
            $get_info = $service->fetchMetadata($link);
            if ($get_info["status"]) {
                $response = array(
                    "success" => true,
                    "title" => isset($get_info["title"]) ? $get_info["title"] : "",
                    "image" => isset($get_info["image"]) ? $get_info["image"] : "",
                    "link" => $link,
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => $get_info["error"]
                );
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Please enter a valid Link!",
            );
        }
        return response()->json($response);
    }
}
