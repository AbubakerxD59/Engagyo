<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use App\Models\Pinterest;
use Illuminate\Support\Facades\Auth;

class PinterestController extends Controller
{
    private $pinterestService;
    private $pinterest;
    private $account;
    public function __construct(Pinterest $pinterest, Account $account)
    {
        $this->pinterestService = new PinterestService();
        $this->pinterest = $pinterest;
        $this->account = $account;
    }
    public function pinterestCallback(Request $request)
    {
        if ($request->has('code') && $request->has('state')) {
            $token = $this->pinterestService->getOauthToken($request->code);
            if (isset($token["access_token"])) {
                $me = $this->pinterestService->me($token["access_token"]);
                if (isset($me['id'])) {
                    $user = Auth::user();
                    $profile_pic = saveImageFromUrl($me["profile_image"]) ? saveImageFromUrl($me["profile_image"]) : '';
                    $data = [
                        "user_id" => $user->id,
                        "pin_id" => $me["id"],
                        "username" => $me["username"],
                        "about" => $me["about"],
                        "profile_image" => $profile_pic,
                        "board_count" => $me["board_count"],
                        "pin_count" => $me["pin_count"],
                        "following_count" => $me["following_count"],
                        "follower_count" => $me["follower_count"],
                        "monthly_views" => $me["monthly_views"] > 0 ? $me["monthly_views"] : 0,
                    ];
                    $this->pinterest->updateOrCreate(["user_id" => $user->id, "pin_id" => $me["id"]], $data);
                    $this->account->updateOrCreate(["user_id" => $user->id, "acc_id" => $me["id"]], ["user_id" => $user->id, "acc_id" => $me["id"], "type" => "Pinterest", "status" => 1]);

                    $boards = $this->pinterestService->getBoards($token["access_token"]);
                    if (isset($boards['items'])) {
                        session_set('account', 'Pinterest');
                        session_set('profile_pic', $profile_pic);
                        session_set('items', $boards["items"]);
                    }
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
