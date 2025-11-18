<?php

namespace App\Http\Controllers;

use App\Services\HtmlParseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralController extends Controller
{
    public function previewLink(Request $request)
    {
        $user = Auth::user();
        $accounts = $user->getAccounts();
        $check = $accounts->where("type", "!=", "pinterest");
        $pinterest_active = count($check) > 0 ? 0 : 1;
        $link = $request->link;
        if (!empty($link)) {
            $max_tries = 3;
            $retry = 1;
            $service = new HtmlParseService($pinterest_active);
            while ($max_tries >= $retry) {
                $get_info = $service->get_info($link, 1);
                if ($get_info["status"] && !empty($get_info["title"]) && !empty($get_info["image"])) {
                    $response = array(
                        "success" => true,
                        "title" => isset($get_info["title"]) ? $get_info["title"] : "",
                        "image" => isset($get_info["image"]) ? $get_info["image"] : "",
                        "link" => $link,
                    );
                } else {
                    $response = array(
                        "success" => false,
                        "message" => $get_info["message"]
                    );
                    $retry++;
                    sleep(5);
                }
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
