<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Facebook;
use App\Models\Feature;
use App\Models\InstagramAccount;
use App\Models\Page;
use App\Models\User;
use App\Services\FacebookService;
use App\Services\FeatureUsageService;
use App\Services\InstagramLoginService;
use App\Services\SocialMediaLogService;
use Illuminate\Http\Request;
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
        $this->facebookService = new FacebookService;
        $this->facebook = $facebook;
        $this->page = $page;
        $this->logService = new SocialMediaLogService;
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

            return redirect(route('panel.accounts'))->with('error', 'Invalid Code!');
        }

        $user = User::with('facebook')->findOrFail(Auth::guard('user')->id());

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
                'email' => $socialiteUser->getEmail(),
            ], 'info');

            $tokenMeta = $this->facebookService->validateAccessToken($access_token);
            if ($tokenMeta['success'] && isset($tokenMeta['data'])) {
                $expires_in = $tokenMeta['data']->getField('data_access_expires_at') ?? null;
            }
        } catch (\Exception $e) {
            // Fall back to Facebook SDK token exchange if Socialite fails on callback
            $getAccessToken = $this->facebookService->getAccessToken();
            if (! $getAccessToken['success']) {
                $errorMessage = $getAccessToken['message'] ?? 'Failed to get access token from Facebook.';
                $this->logService->logApiError('facebook', '/oauth/access_token', $errorMessage, ['user_id' => $user->id]);

                return redirect(route('panel.accounts'))->with('error', $errorMessage);
            }

            $data = $getAccessToken['data'];
            $meta_data = $data['metadata'];
            $access_token = $data['access_token'];
            $expires_in = $meta_data->getField('data_access_expires_at');
            $me = $this->facebookService->me($access_token);

            if ($me['success'] && isset($me['data'])) {
                $meData = $me['data'];
                $this->logService->log('facebook', 'me_api', 'User info retrieved successfully', [
                    'user_id' => $user->id,
                    'fb_id' => $meData['id'] ?? null,
                    'username' => $meData['name'] ?? null,
                    'email' => $meData['email'] ?? null,
                ], 'info');
                $image = $meData->getPicture();
                $fb_id = $meData['id'];
                $username = $meData['name'];
                $profile_image = saveImageFromUrl($image['url']) ? saveImageFromUrl($image['url']) : '';
            } else {
                $this->logService->logApiError('facebook', '/me', $me['message'] ?? 'Failed to get user info', ['user_id' => $user->id]);

                return redirect(route('panel.accounts'))->with('error', $me['message'] ?? 'Failed to retrieve Facebook profile.');
            }
        }

        if ($access_token && $fb_id) {
            $oauthIntent = session()->pull('facebook_oauth_intent', 'facebook');

            $data = [
                'fb_id' => $fb_id,
                'username' => $username,
                'profile_image' => $profile_image,
                'access_token' => $access_token,
                'expires_in' => $expires_in,
            ];
            $facebookAccount = $user->facebook()->updateOrCreate(['fb_id' => $fb_id], $data);
            $facebook = Facebook::with('pages')->where('fb_id', $fb_id)->first();
            $this->logService->logAccountConnection('facebook', $facebookAccount->id, $username, 'connected');

            if ($oauthIntent === 'instagram') {
                $response = $this->syncInstagramBusinessAccounts($user, $facebookAccount, $access_token);

                return redirect(route('panel.accounts'))->with($response['success'], $response['message']);
            }

            $pages = $this->facebookService->pages($access_token);
            if ($pages['success']) {
                $items = $pages['data'];
                $pagesData = [];
                $key = 0;
                $facebookDbId = $facebookAccount->id;

                foreach ($items as $page) {
                    $connected = $this->page->where('user_id', $user->id)->connected(['fb_id' => $facebookDbId, 'page_id' => $page->getField('id')])->first() ? true : false;
                    $pageProfileImage = $this->facebookService->pageProfileImage($access_token, $page->getField('id'));
                    if ($pageProfileImage['success']) {
                        $pageProfileImage = $pageProfileImage['data'];
                        $pageProfileImage = $pageProfileImage->getField('url');
                    } else {
                        $pageProfileImage = '';
                    }

                    $pagesData['items'][$key] = [
                        'id' => $page->getField('id'),
                        'name' => $page->getField('name'),
                        'access_token' => $page->getField('access_token'),
                        'connected' => $connected,
                        'profile_image' => saveImageFromUrl($pageProfileImage) ? saveImageFromUrl($pageProfileImage) : '',
                    ];
                    $key++;
                }
                session_set('facebook', $facebook);
                session_set('facebook_auth', '1');
                session_set('account', 'Facebook');
                session_set('items', $pagesData['items'] ?? []);
                $response = ['success' => 'success', 'message' => 'Facebook Authorization completed!'];
            } else {
                $errorMessage = $pages['message'] ?? 'Failed to fetch Facebook pages.';
                $this->logService->logApiError('facebook', '/pages', $errorMessage, ['user_id' => $user->id]);
                $response = ['success' => 'error', 'message' => $errorMessage];
            }
        } else {
            $this->logService->log('facebook', 'callback_error', 'Failed to obtain access token', ['user_id' => $user->id], 'error');

            return redirect(route('panel.accounts'))->with('error', 'Failed to connect Facebook.');
        }

        return redirect(route('panel.accounts'))->with($response['success'], $response['message']);
    }

    /**
     * Persist Instagram Business accounts discovered via /me/accounts after Instagram-intent OAuth.
     *
     * @return array{success: string, message: string}
     */
    private function syncInstagramBusinessAccounts(User $user, Facebook $facebookAccount, string $userAccessToken): array
    {
        /** @var FeatureUsageService $featureUsageService */
        $featureUsageService = app(FeatureUsageService::class);

        $pagesResult = $this->facebookService->meAccountsWithInstagram($userAccessToken);
        if (! $pagesResult['success']) {
            $msg = $pagesResult['message'] ?? 'Failed to load Facebook Pages.';
            $this->logService->logApiError('facebook', '/me/accounts', $msg, ['user_id' => $user->id, 'flow' => 'instagram']);

            return [
                'success' => 'error',
                'message' => $msg.' Your Facebook login was saved. Confirm Instagram permissions in your Meta app and that your Instagram Business profile is linked to a Facebook Page.',
            ];
        }

        $edge = $pagesResult['data'];
        $eligiblePages = 0;
        $newlyConnected = 0;
        $updated = 0;
        $skippedQuota = false;
        $seenIgIds = [];

        foreach ($edge as $page) {
            $pageId = $page->getField('id');
            $pageToken = $page->getField('access_token');
            $igNode = $page->getField('instagram_business_account');

            if (! $igNode || ! $pageId || ! $pageToken) {
                continue;
            }

            $igId = null;
            $igUsername = null;
            $igName = null;
            $igPicUrl = null;

            if ($igNode instanceof \Facebook\GraphNodes\GraphNode) {
                $igId = $igNode->getField('id');
                $igUsername = $igNode->getField('username');
                $igName = $igNode->getField('name');
                $igPicUrl = $igNode->getField('profile_picture_url');
            } elseif (is_array($igNode)) {
                $igId = $igNode['id'] ?? null;
                $igUsername = $igNode['username'] ?? null;
                $igName = $igNode['name'] ?? null;
                $igPicUrl = $igNode['profile_picture_url'] ?? null;
            }

            if (! $igId) {
                continue;
            }

            if (isset($seenIgIds[$igId])) {
                continue;
            }
            $seenIgIds[$igId] = true;

            $eligiblePages++;

            $existing = InstagramAccount::where('user_id', $user->id)->where('ig_user_id', $igId)->first();
            $didIncrement = false;
            if (! $existing) {
                $result = $featureUsageService->checkAndIncrement($user, Feature::$features_list[0], 1);
                if (! $result['allowed']) {
                    $skippedQuota = true;

                    continue;
                }
                $didIncrement = true;
            }

            if (empty($igPicUrl) && $pageToken && $igId) {
                $pic = $this->facebookService->instagramProfilePictureUrl($pageToken, $igId);
                if (! empty($pic['success']) && ! empty($pic['url'])) {
                    $igPicUrl = $pic['url'];
                }
            }

            $previousProfile = $existing ? $existing->getRawOriginal('profile_image') : null;
            $profileStored = $previousProfile;
            if (! empty($igPicUrl) && filter_var($igPicUrl, FILTER_VALIDATE_URL)) {
                $savedLocal = saveImageFromUrl($igPicUrl);
                if ($savedLocal) {
                    $profileStored = $savedLocal;
                } else {
                    $profileStored = $igPicUrl;
                }
            }

            try {
                $row = InstagramAccount::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'ig_user_id' => $igId,
                    ],
                    [
                        'facebook_id' => $facebookAccount->id,
                        'page_id' => (string) $pageId,
                        'connection_source' => InstagramLoginService::CONNECTION_FACEBOOK_PAGE,
                        'username' => $igUsername ?: ($igName ?: $igId),
                        'name' => $igName,
                        'profile_image' => $profileStored,
                        'access_token' => $pageToken,
                        'expires_in' => time(),
                    ]
                );

                if ($row->wasRecentlyCreated) {
                    $newlyConnected++;
                } else {
                    $updated++;
                }

                $this->logService->logAccountConnection('instagram', $row->id, $row->username ?? $igId, 'connected');
            } catch (\Throwable $e) {
                if ($didIncrement) {
                    $user->decrementFeatureUsage(Feature::$features_list[0], 1);
                }
                $this->logService->log('instagram', 'connect_failed', $e->getMessage(), [
                    'user_id' => $user->id,
                    'ig_user_id' => $igId,
                ], 'error');
            }
        }

        if ($eligiblePages === 0) {
            return [
                'success' => 'success',
                'message' => 'Facebook connected. No Instagram Business accounts are linked to your Facebook Pages. Link Instagram to a Page in Meta Business Suite, then use Connect Instagram again.',
            ];
        }

        if ($skippedQuota && $newlyConnected === 0 && $updated === 0) {
            return [
                'success' => 'error',
                'message' => 'Your plan does not allow more social accounts. Upgrade your package to connect Instagram.',
            ];
        }

        $parts = [];
        if ($newlyConnected > 0) {
            $parts[] = $newlyConnected === 1
                ? '1 new Instagram account connected.'
                : "{$newlyConnected} new Instagram accounts connected.";
        }
        if ($updated > 0) {
            $parts[] = $updated === 1
                ? '1 Instagram account refreshed.'
                : "{$updated} Instagram accounts refreshed.";
        }
        $message = implode(' ', $parts);
        if ($skippedQuota) {
            $message .= ' Some Instagram profiles were skipped because you reached your social account limit.';
        }

        return [
            'success' => 'success',
            'message' => $message ?: 'Instagram connection completed.',
        ];
    }

    public function deauthorizeCallback(Request $request)
    {
        $this->logService->log('facebook', 'deauthorize_callback', 'Facebook deauthorize callback received', $request->all(), 'warning');
        info(json_encode($request->all()));

        return true;
    }
}
