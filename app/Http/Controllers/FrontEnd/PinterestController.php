<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Pinterest;
use Illuminate\Support\Facades\Auth;

class PinterestController extends Controller
{
    private $pinterestService;
    private $pinterest;
    private $account;
    private $board;
    public function __construct(Pinterest $pinterest, Board $board)
    {
        $this->pinterestService = new PinterestService();
        $this->pinterest = $pinterest;
        $this->board = $board;
    }
    public function pinterestCallback(Request $request)
    {
        $user = Auth::user();
        if ($request->has('code') && $request->has('state')) {
            $token = $this->pinterestService->getOauthToken($request->code);
            if (isset($token["access_token"])) {
                $me = $this->pinterestService->me($token["access_token"]);
                if (isset($me['id'])) {
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
                        "access_token" => $token["access_token"],
                        "expires_in" => $token["expires_in"],
                        "refresh_token" => $token["refresh_token"],
                        "refresh_token_expires_in" => $token["refresh_token_expires_in"],
                    ];
                    $user->pinterest()->updateOrCreate(["pin_id" => $me["id"]], $data);

                    $boards = $this->pinterestService->getBoards($token["access_token"]);
                    dd($boards);
                    if (isset($boards['items'])) {
                        foreach ($boards["items"] as $key => $board) {
                            $connected = $this->board->connected(['user_id' => $user->id, 'pin_id' => $me["id"], 'board_id' => $board["id"]])->first() ? true : false;
                            $boards["items"][$key]["connected"] = $connected;
                        }
                        session_set('pinterest_auth', '1');
                        session_set('account', 'Pinterest');
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
