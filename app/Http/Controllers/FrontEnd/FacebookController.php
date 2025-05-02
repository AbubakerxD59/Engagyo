<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\Facebook;
use Illuminate\Http\Request;
use App\Services\FacebookService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FacebookController extends Controller
{
    private $facebook;
    private $facebookService;
    public function __construct(Facebook $facebook)
    {
        $this->facebookService = new FacebookService();
        $this->facebook = $facebook;
    }
    public function deleteCallback(Request $request)
    {
        dd($request->all());
    }

    public function facebookCallback(Request $request)
    {
        $code = $request->code;
        if (!empty($code)) {
            $user = Auth::user();
            $getAccessToken = $this->facebookService->getAccessToken();
            if ($getAccessToken["success"]) {
                $access_token = $getAccessToken["data"];
                $me = $this->facebookService->me($access_token);
                $profile = $me["data"];
                $image = $profile->getPicture();
                $data = [
                    "fb_id" => $profile["id"],
                    "username" => $profile["name"],
                    "profile_image" => saveImageFromUrl($image["url"]) ? saveImageFromUrl($image["url"]) : '',
                    "access_token" => $access_token,
                ];
                $user->facebook()->updateOrCreate(["fb_id" => $profile["id"]], $data);
                dd($data);
            } else {
                return redirect()->route("panel.accounts")->with("error", $getAccessToken["message"]);
            }
        } else {
            return redirect()->route("panel.accounts")->with("error", "Invalid Code!");
        }
    }

    public function deauthorizeCallback(Request $request)
    {
        dd($request->all());
    }
}
