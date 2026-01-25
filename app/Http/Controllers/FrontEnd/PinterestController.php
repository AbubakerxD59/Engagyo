<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PinterestService;
use App\Services\SocialMediaLogService;
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
    private $logService;
    public function __construct(Pinterest $pinterest, Board $board)
    {
        $this->pinterestService = new PinterestService();
        $this->pinterest = $pinterest;
        $this->board = $board;
        $this->logService = new SocialMediaLogService();
    }
    public function pinterestCallback(Request $request)
    {
        $user = User::with("pinterest")->find(Auth::guard('user')->id());
        if ($request->has('code') && $request->has('state')) {
            $code = $request->code;
            $state = $request->state;
            $token = $this->pinterestService->getOauthToken($code);
            if (isset($token["access_token"])) {
                $me = $this->pinterestService->me($token["access_token"]);

                // Log "me" API call
                if (isset($me['id'])) {
                    $this->logService->log('pinterest', 'me_api', 'User info retrieved successfully', [
                        'user_id' => $user->id,
                        'pin_id' => $me['id'] ?? null,
                        'username' => $me['username'] ?? null,
                        'board_count' => $me['board_count'] ?? null,
                        'pin_count' => $me['pin_count'] ?? null
                    ], 'info');
                } else {
                    $this->logService->logApiError('pinterest', '/user_account', 'Failed to get user info', ['user_id' => $user->id]);
                }

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
                    $pinterestAccount = $user->pinterest()->updateOrCreate(["pin_id" => $me["id"]], $data);

                    // Pinterest Account
                    $pinterest = Pinterest::with("board")->where('pin_id', $me["id"])->first();
                    // Log account connection
                    $this->logService->logAccountConnection('pinterest', $pinterestAccount->id, $me["username"], 'connected');

                    $boards = $this->pinterestService->getBoards($token["access_token"]);
                    if (isset($boards['items'])) {
                        // Get the Pinterest account's database ID (not the API pin_id)
                        $pinterestDbId = $pinterestAccount->id;

                        foreach ($boards["items"] as $key => $board) {
                            // Check if board is already connected using the Pinterest account's database ID
                            $connected = $this->board->connected([
                                'pin_id' => $pinterestDbId,
                                'board_id' => $board["id"]
                            ])->first() ? true : false;
                            $boards["items"][$key]["connected"] = $connected;
                        }
                        session_set('pinterest', $pinterest);
                        session_set('pinterest_auth', '1');
                        session_set('account', 'Pinterest');
                        session_set('items', $boards["items"]);
                    }
                    $response = [
                        "success" => "success",
                        "message" => "Pinterest Authorization completed!"
                    ];
                } else {
                    $this->logService->logApiError('pinterest', '/user_account', 'Failed to get user information', ['user_id' => $user->id]);
                    $response = [
                        "success" => "error",
                        "message" => "Something went Wrong!"
                    ];
                }
            } else {
                $this->logService->logApiError('pinterest', '/oauth/token', 'Failed to get access token', ['user_id' => $user->id]);
                $response = [
                    "success" => "error",
                    "message" => "Invalid Code!"
                ];
            }
        } else {
            $this->logService->log('pinterest', 'callback_error', 'Missing code or state parameter', ['user_id' => Auth::guard('user')->id()], 'error');
            $response = [
                "success" => "error",
                "message" => "Something went Wrong!"
            ];
        }
        return redirect(route("panel.accounts"))->with($response["success"], $response["message"]);
    }
}
