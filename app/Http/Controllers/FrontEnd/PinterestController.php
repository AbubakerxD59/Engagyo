<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PinterestController extends Controller
{
    private $pinterestService;
    private $user;
    public function __construct(User $user)
    {
        $this->pinterestService = new PinterestService();
        $this->user = $user;
    }
    public function pinterestCallback(Request $request)
    {
        if ($request->has('code') && $request->has('state')) {
            $token = $this->pinterestService->getOauthToken($request->code);
            if (isset($token["access_token"])) {
                $me = $this->pinterestService->me($token["access_token"]);
                if (isset($me['id'])) {
                    $user = Auth::user();
                    $data = [
                        "user_id" => $user->id,
                        "pin_id" => $me["id"],
                        "username" => $me["username"],
                        "about" => $me["about"],
                        "profile_image" => $me["profile_image"],
                        "board_count" => $me["board_count"],
                        "pin_count" => $me["pin_count"],
                        "following_count" => $me["following_count"],
                        "follower_count" => $me["follower_count"],
                        "monthly_views" => $me["monthly_views"],
                    ];
                    $this->user->updateOrCreate(["user_id" => $user->id, "pin_id" => $me["id"]], $data);
                    $response = [
                        "success" => "success",
                        "message" => "Pinterest Authorization completed!"
                    ];
                } else {
                    $response = [
                        "success" => "error",
                        "message" => "Something went Wrong!"
                    ];
                }
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
