<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Services\FacebookService;
use Illuminate\Http\Request;

class FacebookController extends Controller
{
    private $facebookService;
    public function __construct()
    {
        $this->facebookService = new FacebookService();
    }
    public function deleteCallback(Request $request)
    {
        dd($request->all());
    }

    public function facebookCallback(Request $request)
    {
        $code = $request->code;
        if (!empty($code)) {
            $getAccessToken = $this->facebookService->getAccessToken();
            if ($getAccessToken["success"]) {
                $access_token = $getAccessToken["data"];
                $me = $this->facebookService->me($access_token);
                $user = $me["data"];
                dd($user);
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
