<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ThreadsService;
use App\Services\SocialMediaLogService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ThreadsController extends Controller
{
    private ThreadsService $threadsService;
    private SocialMediaLogService $logService;

    public function __construct()
    {
        $this->threadsService = new ThreadsService();
        $this->logService = new SocialMediaLogService();
    }

    public function threadsCallback(Request $request)
    {
        $userId = Auth::guard('user')->id();
        $user = User::with('threads')->find($userId);

        if (!$user) {
            return redirect(route('panel.accounts'))->with('error', 'User session expired. Please login again.');
        }

        $sessionState = session_get('threads_oauth_state');
        if (empty($request->state) || empty($sessionState) || $request->state !== $sessionState) {
            $this->logService->log('threads', 'callback_error', 'Invalid OAuth state', ['user_id' => $userId], 'error');
            return redirect(route('panel.accounts'))->with('error', 'Invalid OAuth state for Threads authorization.');
        }

        if (!$request->has('code')) {
            $this->logService->log('threads', 'callback_error', 'Missing authorization code', ['user_id' => $userId], 'error');
            return redirect(route('panel.accounts'))->with('error', 'Invalid authorization code for Threads.');
        }

        $tokenResponse = $this->threadsService->getOauthToken($request->code);
        if (empty($tokenResponse['access_token'])) {
            $errorMessage = $tokenResponse['error_message'] ?? $tokenResponse['message'] ?? 'Failed to get Threads access token.';
            $this->logService->logApiError('threads', '/oauth/access_token', $errorMessage, ['user_id' => $userId]);
            return redirect(route('panel.accounts'))->with('error', $errorMessage);
        }

        $accessToken = $tokenResponse['access_token'];
        $expiresIn = $tokenResponse['expires_in'] ?? null;

        $longLivedTokenResponse = $this->threadsService->getLongLivedToken($accessToken);
        if (!empty($longLivedTokenResponse['access_token'])) {
            $accessToken = $longLivedTokenResponse['access_token'];
            $expiresIn = $longLivedTokenResponse['expires_in'] ?? $expiresIn;
        }

        $me = $this->threadsService->me($accessToken);
        dd($me);
        if (empty($me['id']) || empty($me['username'])) {
            $errorMessage = $me['error_message'] ?? $me['message'] ?? 'Failed to fetch Threads user profile.';
            $this->logService->logApiError('threads', '/me', $errorMessage, ['user_id' => $userId]);
            return redirect(route('panel.accounts'))->with('error', $errorMessage);
        }

        $profileImage = '';
        if (!empty($me['threads_profile_picture_url'])) {
            $savedProfileImage = saveImageFromUrl($me['threads_profile_picture_url']);
            $profileImage = $savedProfileImage ? $savedProfileImage : '';
        }

        $threadAccount = $user->threads()->updateOrCreate(
            ['threads_id' => $me['id']],
            [
                'user_id' => $user->id,
                'threads_id' => $me['id'],
                'username' => $me['username'],
                'profile_image' => $profileImage,
                'access_token' => $accessToken,
                'expires_in' => $expiresIn,
                'refresh_token' => $longLivedTokenResponse['refresh_token'] ?? null,
            ]
        );

        $this->logService->logAccountConnection('threads', $threadAccount->id, $threadAccount->username, 'connected');
        session_delete('threads_oauth_state');
        session_set('threads_auth', '1');

        return redirect(route('panel.accounts'))->with('success', 'Threads authorization completed!');
    }

    public function uninstallCallback(Request $request)
    {
        $this->logService->log('threads', 'uninstall_callback', 'Threads uninstall callback received', $request->all(), 'warning');

        return true;
    }

    public function deleteCallback(Request $request)
    {
        $this->logService->log('threads', 'delete_callback', 'Threads delete callback received', $request->all(), 'warning');

        return true;
    }
}
