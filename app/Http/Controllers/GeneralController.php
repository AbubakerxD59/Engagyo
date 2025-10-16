<?php

namespace App\Http\Controllers;

use App\Services\HtmlParseService;
use DOMDocument;
use Illuminate\Http\Request;
use App\Services\FeedService;
use Illuminate\Support\Facades\Auth;

class GeneralController extends Controller
{
    public function previewLink(Request $request)
    {
        return [
            "success" => true,
            "title" => "They were thousands of miles from home in a hospital in India. Here's how they met and fell in love | CNN",
            "image" => "https=>\/\/media.cnn.com\/api\/v1\/images\/stellar\/prod\/img-1333-copy.jpg?c=16x9&q=w_800,c_fill",
            "link" => $request->link,
        ];
        $user = Auth::user();
        $accounts = $user->getAccounts();
        $check = $accounts->where("type", "!=", "pinterest");
        $mode = count($check) > 0 ? 0 : 1;
        $link = $request->link;
        if (!empty($link)) {
            $htmlParse = new HtmlParseService();
            $get_info = $htmlParse->get_info($link, $mode);
            if (isset($get_info["message"])) {
                $response = array(
                    "success" => false,
                    "message" => $get_info["message"]
                );
            } else {
                $response = array(
                    "success" => true,
                    "title" => isset($get_info["title"]) ? $get_info["title"] : "",
                    "image" => isset($get_info["image"]) ? $get_info["image"] : "",
                    "link" => $link,
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
