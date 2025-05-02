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
            $access_token = $this->facebookService->getAccessToken();
            if ($access_token["success"]) {
                dd($access_token);
            } else {
                return redirect()->route("panel.accounts")->with("error", $access_token["message"]);
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
