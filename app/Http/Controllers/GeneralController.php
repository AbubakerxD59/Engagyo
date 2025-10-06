<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FeedService;

class GeneralController extends Controller
{
    public function previewLink(Request $request)
    {
        $link = $request->link;
        if (!empty($link)) {
            $feedService = new FeedService([]);
            $fetchUrlContent = $feedService->fetchUrlContent($link);
            dd($fetchUrlContent);
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
