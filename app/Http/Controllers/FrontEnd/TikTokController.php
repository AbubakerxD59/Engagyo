<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\User;
use App\Models\Tiktok;
use Illuminate\Http\Request;
use App\Services\TikTokService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TikTokController extends Controller
{
    private $tiktokService;
    private $tiktok;

    public function __construct(TikTok $tiktok)
    {
        $this->tiktokService = new TikTokService();
        $this->tiktok = $tiktok;
    }

    public function tiktokCallback(Request $request)
    {
        $user = User::with("tiktok")->find(Auth::id());

        if ($request->has('code')) {
            $tokenResponse = $this->tiktokService->getUserAccessToken($request->code);

            if ($tokenResponse["success"]) {
                $tokenData = $tokenResponse["data"];

                if (isset($tokenData["access_token"])) {
                    $access_token = $tokenData["access_token"];
                    $userInfo = $this->tiktokService->me($access_token);

                    if (!empty($userInfo)) {
                        $tiktok_id = $userInfo["open_id"] ?? $userInfo["union_id"] ?? null;

                        if ($tiktok_id) {
                            $profile_pic = isset($userInfo["avatar_url"]) && !empty($userInfo["avatar_url"])
                                ? saveImageFromUrl($userInfo["avatar_url"])
                                : '';

                            $data = [
                                "user_id" => $user->id,
                                "tiktok_id" => $tiktok_id,
                                "username" => $userInfo["username"] ?? $userInfo["display_name"] ?? '',
                                "display_name" => $userInfo["display_name"] ?? $userInfo["username"] ?? '',
                                "profile_image" => $profile_pic,
                                "access_token" => $access_token,
                                "expires_in" => $tokenData["expires_in"] ?? 3600,
                                "refresh_token" => $tokenData["refresh_token"] ?? null,
                                "refresh_token_expires_in" => $tokenData["refresh_token_expires_in"] ?? null,
                            ];

                            $user->tiktok()->updateOrCreate(["tiktok_id" => $tiktok_id], $data);

                            session_set('tiktok_auth', '1');
                            session_set('account', 'TikTok');

                            $response = [
                                "success" => "success",
                                "message" => "TikTok Authorization completed!"
                            ];
                        } else {
                            $response = [
                                "success" => "error",
                                "message" => "Failed to get TikTok user ID."
                            ];
                        }
                    } else {
                        $response = [
                            "success" => "error",
                            "message" => "Failed to get TikTok user information."
                        ];
                    }
                } else {
                    $response = [
                        "success" => "error",
                        "message" => "Failed to get access token from TikTok."
                    ];
                }
            } else {
                $response = [
                    "success" => "error",
                    "message" => $tokenResponse["error"] ?? "Failed to authenticate with TikTok."
                ];
            }
        } else {
            $response = [
                "success" => "error",
                "message" => "Invalid authorization code!"
            ];
        }

        return redirect()->route("panel.accounts")->with($response["success"], $response["message"]);
    }
}
