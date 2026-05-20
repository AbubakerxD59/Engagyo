<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserEmailIsVerified
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        $user = Auth::guard('user')->user();

        if (! $user) {
            return $request->expectsJson()
                ? abort(403, 'Unauthenticated.')
                : Redirect::guest(route('frontend.showLogin'));
        }

        $mustVerify = $user instanceof MustVerifyEmail
            && method_exists($user, 'requiresEmailVerification')
            && $user->requiresEmailVerification()
            && ! $user->hasVerifiedEmail();

        if ($mustVerify) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(route($redirectToRoute ?: 'frontend.verification.notice'));
        }

        return $next($request);
    }
}
