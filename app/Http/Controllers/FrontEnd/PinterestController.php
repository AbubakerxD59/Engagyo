<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Services\PinterestService;
use DirkGroenen\Pinterest\Pinterest;
use Illuminate\Http\Request;

class PinterestController extends Controller
{
    private $pinterestService;
    public function __construct()
    {
        $this->pinterestService = new PinterestService();
    }
    public function pinterestCallback(Request $request)
    {
        if ($request->has('code') && $request->has('state')) {
            $token = $this->pinterestService->getOauthToken($request->code);
            if (isset($token->access_token)) {
                $this->pinterestService->setOAuthToken($token->access_token);
                $me = $this->pinterestService->me();
                dd($me);
            } else {
                $response = [
                    "success" => "error",
                    "message" => "Invalid Code!"
                ];
            }
        } else {
            $response = [
                "success" => "error",
                "message" => "Something went Wrong!"
            ];
        }
        return redirect(route("panel.accounts"))->with($response["success"], $response["message"]);
    }
}
