<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

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
        return view('frontend.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('frontend.showLogin')
                ->with('success', 'Your password has been reset. You can sign in now.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', __($status));
    }
}
