<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontEnd\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return view('frontend.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || $user->getRole() !== 'User') {
            return back()->with('success', 'If an account exists for that email, we have sent a password reset link.');
        }

        $status = Password::broker('users')->sendResetLink(
            ['email' => $request->email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'If an account exists for that email, we have sent a password reset link.');
        }

        return back()->with('error', __($status));
    }

    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email', old('email'));

        return view('frontend.auth.reset-password', [
            'token' => $token,
            'email' => $email,
            'emailLocked' => (bool) $request->query('email'),
        ]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || $user->getRole() !== 'User') {
            return $this->redirectToResetForm($request)
                ->with('error', __('passwords.user'));
        }

        $broker = Password::broker('users');

        if (! $broker->tokenExists($user, $request->input('token'))) {
            return $this->redirectToResetForm($request)
                ->with('error', __('passwords.token'));
        }

        $saved = $user->forceFill([
            'password' => $request->input('password'),
            'remember_token' => Str::random(60),
        ])->save();

        if (! $saved) {
            return $this->redirectToResetForm($request)
                ->with('error', 'Unable to save your new password. Please try again.');
        }

        event(new PasswordReset($user));
        $broker->deleteToken($user);

        return redirect()->route('frontend.showLogin')
            ->with('success', 'Your password has been reset. You can sign in now.');
    }

    /**
     * Always return to the GET reset form so the token stays in the URL and hidden field.
     */
    protected function redirectToResetForm(Request $request)
    {
        return redirect()
            ->route('frontend.password.reset', [
                'token' => $request->input('token'),
                'email' => $request->input('email'),
            ])
            ->withInput($request->only(['email', 'token']));
    }
}
