<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\HtmlParseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralController extends Controller
{
    public function previewLink(Request $request)
    {
        $user = User::with("pages.facebook", "boards.pinterest")->findOrFail(Auth::id());
        $accounts = $user->getAccounts();
        $check = $accounts->where("schedule_status", "active")->where("type", "!=", "pinterest")->first();
        $pinterest_active = $check ? false : true;
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
                    break;
                } else {
                    $response = array(
                        "success" => false,
                        "message" => isset($get_info["message"]) ? $get_info["message"] : "Something went wrong!"
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
