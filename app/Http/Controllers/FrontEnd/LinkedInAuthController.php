<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\User;
use App\Services\FeatureUsageService;
use App\Services\LinkedInService;
use App\Services\SocialMediaLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LinkedInAuthController extends Controller
{
    private LinkedInService $linkedInService;

    private SocialMediaLogService $logService;

    public function __construct()
    {
        $this->linkedInService = new LinkedInService();
        $this->logService = new SocialMediaLogService();
    }

    public function linkedInCallback(Request $request, FeatureUsageService $featureUsageService)
    {
        $userId = Auth::guard('user')->id();
        $user = User::with('linkedin')->find($userId);

        if (! $user) {
            return redirect(route('panel.accounts'))->with('error', 'User session expired. Please login again.');
        }

        $sessionState = session_get('linkedin_oauth_state');
        if (empty($request->state) || empty($sessionState) || $request->state !== $sessionState) {
            $this->logService->log('linkedin', 'callback_error', 'Invalid OAuth state', ['user_id' => $userId], 'error');

            return redirect(route('panel.accounts'))->with('error', 'Invalid OAuth state for LinkedIn authorization.');
        }

        if (! $request->has('code')) {
            $this->logService->log('linkedin', 'callback_error', 'Missing authorization code', ['user_id' => $userId], 'error');

            return redirect(route('panel.accounts'))->with('error', 'Invalid authorization code for LinkedIn.');
        }

        $tokenResponse = $this->linkedInService->getAccessToken((string) $request->code);
        if (empty($tokenResponse['access_token'])) {
            $errorMessage = $tokenResponse['error_description'] ?? $tokenResponse['message'] ?? 'Failed to get LinkedIn access token.';
            $this->logService->logApiError('linkedin', '/oauth/v2/accessToken', $errorMessage, ['user_id' => $userId]);

            return redirect(route('panel.accounts'))->with('error', $errorMessage);
        }

        $accessToken = $tokenResponse['access_token'];
        $userInfo = $this->linkedInService->getUserInfo($accessToken);
        if (empty($userInfo['sub'])) {
            $errorMessage = $userInfo['message'] ?? 'Failed to fetch LinkedIn user profile.';
            $this->logService->logApiError('linkedin', '/v2/userinfo', $errorMessage, ['user_id' => $userId]);

            return redirect(route('panel.accounts'))->with('error', $errorMessage);
        }

        $existing = $user->linkedin()->where('linkedin_id', $userInfo['sub'])->first();
        $didIncrement = false;
        if (! $existing) {
            $result = $featureUsageService->checkAndIncrement($user, Feature::$features_list[0], 1);
            if (! $result['allowed']) {
                return redirect(route('panel.accounts'))->with('error', $result['message']);
            }
            $didIncrement = true;
        }

        $profileImage = '';
        if (! empty($userInfo['picture'])) {
            $savedProfileImage = saveImageFromUrl($userInfo['picture']);
            $profileImage = $savedProfileImage ?: '';
        }

        try {
            $payload = [
                'user_id' => $user->id,
                'linkedin_id' => $userInfo['sub'],
                'username' => $userInfo['name'] ?? ($userInfo['given_name'] ?? $userInfo['email'] ?? 'LinkedIn User'),
                'email' => $userInfo['email'] ?? null,
                'profile_image' => $profileImage,
                'access_token' => $accessToken,
                'expires_in' => $tokenResponse['expires_in'] ?? null,
                'refresh_token' => $tokenResponse['refresh_token'] ?? null,
            ];

            // Some environments may still have the base linkedins schema without this column.
            if (Schema::hasColumn('linkedins', 'url_shortener_enabled')) {
                $payload['url_shortener_enabled'] = in_array('linkedin', $user->url_shorten_platforms ?? []);
            }

            $linkedinAccount = $user->linkedin()->updateOrCreate(
                ['linkedin_id' => $userInfo['sub']],
                $payload
            );
        } catch (\Throwable $e) {
            if ($didIncrement) {
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);
            }

            $this->logService->log('linkedin', 'connect_failed', $e->getMessage(), [
                'user_id' => $userId,
                'linkedin_id' => $userInfo['sub'],
            ], 'error');

            return redirect(route('panel.accounts'))->with('error', 'Failed to connect LinkedIn account.');
        }

        $this->logService->logAccountConnection('linkedin', $linkedinAccount->id, $linkedinAccount->username, 'connected');
        session_delete('linkedin_oauth_state');

        return redirect(route('panel.accounts'))->with('success', 'LinkedIn authorization completed!');
    }
}
