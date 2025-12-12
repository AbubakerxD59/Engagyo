<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\Page;
use App\Models\User;
use App\Models\Facebook;
use Illuminate\Http\Request;
use App\Services\FacebookService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FacebookController extends Controller
{
    private $facebookService;
    private $facebook;
    private $page;
    public function __construct(Facebook $facebook, Page $page)
    {
        $this->facebookService = new FacebookService();
        $this->facebook = $facebook;
        $this->page = $page;
    }
    public function deleteCallback(Request $request)
    {
        info(json_encode($request->all()));
        return true;
    }

    public function facebookCallback(Request $request)
    {
        $code = $request->code;
        if (!empty($code)) {
            $user = User::with("facebook")->findOrFail(Auth::id());
            $getAccessToken = $this->facebookService->getAccessToken();
            if ($getAccessToken["success"]) {
                $data = $getAccessToken["data"];
                $meta_data = $data["metadata"];
                $access_token = $data["access_token"];
                $me = $this->facebookService->me($access_token);
                $me = $me["data"];
                $image = $me->getPicture();
                $data = [
                    "fb_id" => $me["id"],
                    "username" => $me["name"],
                    "profile_image" => saveImageFromUrl($image["url"]) ? saveImageFromUrl($image["url"]) : '',
                    "access_token" => $access_token,
                    "expires_in" => $meta_data->getField("data_access_expires_at")
                ];
                $facebookAccount = $user->facebook()->updateOrCreate(["fb_id" => $me["id"]], $data);

                $pages = $this->facebookService->pages($access_token);
                if ($pages["success"]) {
                    $items = $pages["data"];
                    $pages = [];
                    $key = 0;
                    // Get the Facebook account's database ID (not the API fb_id)
                    $facebookDbId = $facebookAccount->id;
                    
                    foreach ($items as $page) {
                        // Check if page is already connected using the Facebook account's database ID
                        $connected = $this->page->connected(['fb_id' => $facebookDbId, 'page_id' => $page->getField("id")])->first() ? true : false;
                        $profile_image = $this->facebookService->pageProfileImage($access_token, $page->getField("id"));
                        if ($profile_image["success"]) {
                            $profile_image = $profile_image["data"];
                            $profile_image = $profile_image->getField("url");
                        } else {
                            $profile_image = '';
                        }

                        $pages["items"][$key] = [
                            "id" => $page->getField("id"),
                            "name" => $page->getField("name"),
                            "access_token" => $page->getField("access_token"),
                            "connected" => $connected,
                            "profile_image" => saveImageFromUrl($profile_image) ? saveImageFromUrl($profile_image) : '',
                        ];
                        $key++;
                    }
                    session_set('facebook_auth', '1');
                    session_set('account', 'Facebook');
                    session_set('items', $pages["items"]);
                    $response = [
                        "success" => "success",
                        "message" => "Facebook Authorization completed!"
                    ];
                } else {
                    $response = [
                        "success" => "error",
                        "message" => $pages["message"] ?? "Failed to fetch Facebook pages."
                    ];
                }
            } else {
                $response = [
                    "success" => "error",
                    "message" => $getAccessToken["message"] ?? "Failed to get access token from Facebook."
                ];
            }
        } else {
            $response = [
                "success" => "error",
                "message" => "Invalid Code!"
            ];
        }
        return redirect(route("panel.accounts"))->with($response["success"], $response["message"]);
    }

    public function deauthorizeCallback(Request $request)
    {
        info(json_encode($request->all()));
        return true;
    }
}
