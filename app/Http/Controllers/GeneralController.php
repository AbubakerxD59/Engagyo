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

        $user = Auth::user();
        $accounts = $user->getAccounts();
        $link = $request->link;
        if (!empty($link)) {
            $htmlParse = new HtmlParseService();
            $get_info = $htmlParse->get_info($link, 0);
            dd($get_info);

            // $html = file_get_contents($link);
            // $doc = new DOMDocument();
            // @$doc->loadHTML($html);
            // $title = "";
            // $image = "";
            // $titles = $doc->getElementsByTagName('title');
            // if ($titles->length > 0) {
            //     $title = $titles->item(0)->nodeValue;
            // }
            // $images = $doc->getElementsByTagName('img');
            // foreach ($images as $img) {
            //     $priority = $img->getAttribute('fetchpriority');
            //     if ($priority && $priority == "high") {
            //         $src = $img->getAttribute('src');
            //         if (!empty($src)) {
            //             $image = $src;
            //             break;
            //         }
            //     }
            // }
            $response = array(
                "success" => true,
                "title" => "Link Image",
            );
        } else {
            $response = array(
                "success" => false,
                "title" => "Please enter a valid Link!",
            );
        }
        return response()->json($response);
    }
}
