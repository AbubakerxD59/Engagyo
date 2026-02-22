<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\Page;
use App\Models\User;
use App\Models\Facebook;
use Illuminate\Http\Request;
use App\Services\FacebookService;
use App\Services\SocialMediaLogService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class FacebookController extends Controller
{
    private $facebookService;
    private $facebook;
    private $page;
    private $logService;
    public function __construct(Facebook $facebook, Page $page)
    {
        $this->facebookService = new FacebookService();
        $this->facebook = $facebook;
        $this->page = $page;
        $this->logService = new SocialMediaLogService();
    }
    public function deleteCallback(Request $request)
    {
        $this->logService->log('facebook', 'delete_callback', 'Facebook delete callback received', $request->all(), 'info');
        info(json_encode($request->all()));
        return true;
    }

    public function facebookCallback(Request $request)
    {
        $code = $request->code;
        if (empty($code)) {
            $this->logService->log('facebook', 'callback_error', 'Invalid authorization code', ['user_id' => Auth::guard('user')->id()], 'error');
            return redirect(route("panel.accounts"))->with('error', 'Invalid Code!');
        }

        $user = User::with("facebook")->findOrFail(Auth::guard('user')->id());

        // Try Socialite first (for callback from FacebookSocialiteService redirect)
        $access_token = null;
        $fb_id = null;
        $username = null;
        $profile_image = '';
        $expires_in = null;

        try {
            $socialiteUser = Socialite::driver('facebook')
                ->redirectUrl(route('facebook.callback'))
                ->user();
            $access_token = $socialiteUser->token;
            $fb_id = $socialiteUser->getId();
            $username = $socialiteUser->getName();
            $profile_image = $socialiteUser->getAvatar() ? (saveImageFromUrl($socialiteUser->getAvatar()) ?: '') : '';

            $this->logService->log('facebook', 'me_api', 'User info retrieved via Socialite', [
                'user_id' => $user->id,
                'fb_id' => $fb_id,
                'username' => $username,
                'email' => $socialiteUser->getEmail()
            ], 'info');

            $tokenMeta = $this->facebookService->validateAccessToken($access_token);
            if ($tokenMeta["success"] && isset($tokenMeta["data"])) {
                $expires_in = $tokenMeta["data"]->getField("data_access_expires_at") ?? null;
            }
        } catch (\Exception $e) {
            // Fall back to Facebook SDK flow (for callback from FacebookService getLoginUrl)
            $getAccessToken = $this->facebookService->getAccessToken();
            if (!$getAccessToken["success"]) {
                $errorMessage = $getAccessToken["message"] ?? "Failed to get access token from Facebook.";
                $this->logService->logApiError('facebook', '/oauth/access_token', $errorMessage, ['user_id' => $user->id]);
                return redirect(route("panel.accounts"))->with('error', $errorMessage);
            }

            $data = $getAccessToken["data"];
            $meta_data = $data["metadata"];
            $access_token = $data["access_token"];
            $expires_in = $meta_data->getField("data_access_expires_at");
            $me = $this->facebookService->me($access_token);

            if ($me["success"] && isset($me["data"])) {
                $meData = $me["data"];
                $this->logService->log('facebook', 'me_api', 'User info retrieved successfully', [
                    'user_id' => $user->id,
                    'fb_id' => $meData["id"] ?? null,
                    'username' => $meData["name"] ?? null,
                    'email' => $meData["email"] ?? null
                ], 'info');
                $image = $meData->getPicture();
                $fb_id = $meData["id"];
                $username = $meData["name"];
                $profile_image = saveImageFromUrl($image["url"]) ? saveImageFromUrl($image["url"]) : '';
            } else {
                $this->logService->logApiError('facebook', '/me', $me["message"] ?? 'Failed to get user info', ['user_id' => $user->id]);
                return redirect(route("panel.accounts"))->with('error', $me["message"] ?? 'Failed to retrieve Facebook profile.');
            }
        }

        if ($access_token && $fb_id) {
            $data = [
                "fb_id" => $fb_id,
                "username" => $username,
                "profile_image" => $profile_image,
                "access_token" => $access_token,
                "expires_in" => $expires_in
            ];
            $facebookAccount = $user->facebook()->updateOrCreate(["fb_id" => $fb_id], $data);
            $facebook = Facebook::with('pages')->where('fb_id', $fb_id)->first();
            $this->logService->logAccountConnection('facebook', $facebookAccount->id, $username, 'connected');

            $pages = $this->facebookService->pages($access_token);
            if ($pages["success"]) {
                $items = $pages["data"];
                $pagesData = [];
                $key = 0;
                $facebookDbId = $facebookAccount->id;

                foreach ($items as $page) {
                    $connected = $this->page->connected(['fb_id' => $facebookDbId, 'page_id' => $page->getField("id")])->first() ? true : false;
                    $pageProfileImage = $this->facebookService->pageProfileImage($access_token, $page->getField("id"));
                    if ($pageProfileImage["success"]) {
                        $pageProfileImage = $pageProfileImage["data"];
                        $pageProfileImage = $pageProfileImage->getField("url");
                    } else {
                        $pageProfileImage = '';
                    }

                    $pagesData["items"][$key] = [
                        "id" => $page->getField("id"),
                        "name" => $page->getField("name"),
                        "access_token" => $page->getField("access_token"),
                        "connected" => $connected,
                        "profile_image" => saveImageFromUrl($pageProfileImage) ? saveImageFromUrl($pageProfileImage) : '',
                    ];
                    $key++;
                }
                session_set('facebook', $facebook);
                session_set('facebook_auth', '1');
                session_set('account', 'Facebook');
                session_set('items', $pagesData["items"] ?? []);
                $response = ["success" => "success", "message" => "Facebook Authorization completed!"];
            } else {
                $errorMessage = $pages["message"] ?? "Failed to fetch Facebook pages.";
                $this->logService->logApiError('facebook', '/pages', $errorMessage, ['user_id' => $user->id]);
                $response = ["success" => "error", "message" => $errorMessage];
            }
        } else {
            $this->logService->log('facebook', 'callback_error', 'Failed to obtain access token', ['user_id' => $user->id], 'error');
            return redirect(route("panel.accounts"))->with('error', 'Failed to connect Facebook.');
        }

        return redirect(route("panel.accounts"))->with($response["success"], $response["message"]);
    }
    public function facebookCallbackOld(Request $request)
    {
        $code = $request->code;
        if (!empty($code)) {
            $user = User::with("facebook")->findOrFail(Auth::guard('user')->id());
            $getAccessToken = $this->facebookService->getAccessToken();
            if ($getAccessToken["success"]) {
                $data = $getAccessToken["data"];
                $meta_data = $data["metadata"];
                $access_token = $data["access_token"];
                $me = $this->facebookService->me($access_token);

                // Log "me" API call
                if ($me["success"] && isset($me["data"])) {
                    $meData = $me["data"];
                    $this->logService->log('facebook', 'me_api', 'User info retrieved successfully', [
                        'user_id' => $user->id,
                        'fb_id' => $meData["id"] ?? null,
                        'username' => $meData["name"] ?? null,
                        'email' => $meData["email"] ?? null
                    ], 'info');
                } else {
                    $this->logService->logApiError('facebook', '/me', $me["message"] ?? 'Failed to get user info', ['user_id' => $user->id]);
                }

                $me = $me["data"];
                $image = $me->getPicture();
                $data = [
                    "fb_id" => $me["id"],
                    "username" => $me["name"],
                    "profile_image" => saveImageFromUrl($image["url"]) ? saveImageFromUrl($image["url"]) : '',
                    "access_token" => $access_token,
                    "expires_in" => $meta_data->getField("data_access_expires_at")
                ];
                $facebookAccount = $user->facebook()->updateOrCreate(["fb_id" => $me["id"]], $data);

                // facebook account 
                $facebook = Facebook::with('pages')->where('fb_id', $me["id"])->first();
                // Log account connection
                $this->logService->logAccountConnection('facebook', $facebookAccount->id, $me["name"], 'connected');

                $pages = $this->facebookService->pages($access_token);
                dd($pages, $access_token);
                if ($pages["success"]) {
                    $items = $pages["data"];
                    $pages = [];
                    $key = 0;
                    // Get the Facebook account's database ID (not the API fb_id)
                    $facebookDbId = $facebookAccount->id;

                    foreach ($items as $page) {
                        // Check if page is already connected using the Facebook account's database ID
                        $connected = $this->page->connected(['fb_id' => $facebookDbId, 'page_id' => $page->getField("id")])->first() ? true : false;
                        $profile_image = $this->facebookService->pageProfileImage($access_token, $page->getField("id"));
                        if ($profile_image["success"]) {
                            $profile_image = $profile_image["data"];
                            $profile_image = $profile_image->getField("url");
                        } else {
                            $profile_image = '';
                        }

                        $pages["items"][$key] = [
                            "id" => $page->getField("id"),
                            "name" => $page->getField("name"),
                            "access_token" => $page->getField("access_token"),
                            "connected" => $connected,
                            "profile_image" => saveImageFromUrl($profile_image) ? saveImageFromUrl($profile_image) : '',
                        ];
                        $key++;
                    }
                    session_set('facebook', $facebook);
                    session_set('facebook_auth', '1');
                    session_set('account', 'Facebook');
                    session_set('items', $pages["items"] ?? []);
                    $response = [
                        "success" => "success",
                        "message" => "Facebook Authorization completed!"
                    ];
                } else {
                    $errorMessage = $pages["message"] ?? "Failed to fetch Facebook pages.";
                    $this->logService->logApiError('facebook', '/pages', $errorMessage, ['user_id' => $user->id]);
                    $response = [
                        "success" => "error",
                        "message" => $errorMessage
                    ];
                }
            } else {
                $errorMessage = $getAccessToken["message"] ?? "Failed to get access token from Facebook.";
                $this->logService->logApiError('facebook', '/oauth/access_token', $errorMessage, ['user_id' => $user->id]);
                $response = [
                    "success" => "error",
                    "message" => $errorMessage
                ];
            }
        } else {
            $this->logService->log('facebook', 'callback_error', 'Invalid authorization code', ['user_id' => Auth::guard('user')->id()], 'error');
            $response = [
                "success" => "error",
                "message" => "Invalid Code!"
            ];
        }
        return redirect(route("panel.accounts"))->with($response["success"], $response["message"]);
    }

    public function deauthorizeCallback(Request $request)
    {
        $this->logService->log('facebook', 'deauthorize_callback', 'Facebook deauthorize callback received', $request->all(), 'warning');
        info(json_encode($request->all()));
        return true;
    }
}
