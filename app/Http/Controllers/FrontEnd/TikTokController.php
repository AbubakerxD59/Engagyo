<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\User;
use App\Models\Tiktok;
use Illuminate\Http\Request;
use App\Services\TikTokService;
use App\Services\SocialMediaLogService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TikTokController extends Controller
{
    private $tiktokService;
    private $tiktok;
    private $logService;

    public function __construct(TikTok $tiktok)
    {
        $this->tiktokService = new TikTokService();
        $this->tiktok = $tiktok;
        $this->logService = new SocialMediaLogService();
    }

    public function tiktokCallback(Request $request)
    {
        $user = User::with("tiktok")->find(Auth::guard('user')->id());

        if ($request->has('code')) {
            $tokenResponse = $this->tiktokService->getUserAccessToken($request->code);

            if ($tokenResponse["success"]) {
                $tokenData = $tokenResponse["data"];

                if (isset($tokenData["access_token"])) {
                    $access_token = $tokenData["access_token"];
                    $userInfo = $this->tiktokService->me($access_token);

                    // Log "me" API call
                    if (!empty($userInfo)) {
                        $this->logService->log('tiktok', 'me_api', 'User info retrieved successfully', [
                            'user_id' => $user->id,
                            'open_id' => $userInfo["open_id"] ?? null,
                            'union_id' => $userInfo["union_id"] ?? null,
                            'username' => $userInfo["username"] ?? $userInfo["display_name"] ?? null,
                            'display_name' => $userInfo["display_name"] ?? null,
                            'meta' => json_encode($userInfo),
                        ], 'info');
                    } else {
                        $this->logService->logApiError('tiktok', '/user/info/', 'Failed to get user info', ['user_id' => $user->id]);
                    }

                    if (!empty($userInfo)) {
                        $tiktok_id = $userInfo["open_id"] ?? $userInfo["union_id"] ?? null;

                        if ($tiktok_id) {
                            $avatar_url = isset($userInfo["meta"]["avatar_url"]) ? $userInfo["meta"]["avatar_url"] : null;
                            $profile_pic = !empty($avatar_url)
                                ? saveImageFromUrl($avatar_url)
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

                            $tiktokAccount = $user->tiktok()->updateOrCreate(["tiktok_id" => $tiktok_id], $data);

                            // Log account connection
                            $this->logService->logAccountConnection('tiktok', $tiktokAccount->id, $userInfo["username"] ?? $userInfo["display_name"] ?? 'Unknown', 'connected');

                            session_set('tiktok_auth', '1');
                            session_set('account', 'TikTok');

                            $response = [
                                "success" => "success",
                                "message" => "TikTok Authorization completed!"
                            ];
                        } else {
                            $this->logService->logApiError('tiktok', '/user/info/', 'Failed to get TikTok user ID', ['user_id' => $user->id]);
                            $response = [
                                "success" => "error",
                                "message" => "Failed to get TikTok user ID."
                            ];
                        }
                    } else {
                        $this->logService->logApiError('tiktok', '/user/info/', 'Failed to get TikTok user information', ['user_id' => $user->id]);
                        $response = [
                            "success" => "error",
                            "message" => "Failed to get TikTok user information."
                        ];
                    }
                } else {
                    $this->logService->logApiError('tiktok', '/oauth/token/', 'Failed to get access token', ['user_id' => $user->id]);
                    $response = [
                        "success" => "error",
                        "message" => "Failed to get access token from TikTok."
                    ];
                }
            } else {
                $errorMessage = $tokenResponse["error"] ?? "Failed to authenticate with TikTok.";
                $this->logService->logApiError('tiktok', '/oauth/token/', $errorMessage, ['user_id' => $user->id]);
                $response = [
                    "success" => "error",
                    "message" => $errorMessage
                ];
            }
        } else {
            $this->logService->log('tiktok', 'callback_error', 'Invalid authorization code', ['user_id' => Auth::guard('user')->id()], 'error');
            $response = [
                "success" => "error",
                "message" => "Invalid authorization code!"
            ];
        }

        return redirect()->route("panel.accounts")->with($response["success"], $response["message"]);
    }
}
