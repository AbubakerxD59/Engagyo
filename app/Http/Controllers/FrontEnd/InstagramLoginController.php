<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InstagramLoginService;
use App\Services\SocialMediaLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstagramLoginController extends Controller
{
    public function callback(Request $request, InstagramLoginService $instagramLoginService)
    {
        $log = app(SocialMediaLogService::class);

        if ($request->filled('error')) {
            $msg = (string) ($request->input('error_description') ?: $request->input('error'));
            $log->log('instagram', 'oauth_error', $msg, $request->only(['error', 'error_description']), 'error');

            return redirect()->route('panel.accounts')->with('error', $msg ?: 'Instagram authorization was cancelled or failed.');
        }

        $state = (string) $request->input('state', '');
        $sessionState = (string) session()->pull('instagram_oauth_state', '');
        if ($sessionState === '' || ! hash_equals($sessionState, $state)) {
            $log->log('instagram', 'oauth_error', 'Invalid OAuth state', ['user_id' => Auth::guard('user')->id()], 'error');

            return redirect()->route('panel.accounts')->with('error', 'Invalid session. Please try connecting Instagram again.');
        }

        $code = $request->input('code');
        if (empty($code) || ! is_string($code)) {
            return redirect()->route('panel.accounts')->with('error', 'Missing authorization code from Instagram.');
        }

        $user = User::findOrFail(Auth::guard('user')->id());
        $response = $instagramLoginService->connectForUser($user, $code);

        return redirect()->route('panel.accounts')->with($response['success'], $response['message']);
    }
}
