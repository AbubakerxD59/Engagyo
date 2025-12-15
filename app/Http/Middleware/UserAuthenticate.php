<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;


class UserAuthenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Check if user is authenticated with user guard
        if (Auth::guard('user')->check()) {
            $user = Auth::guard('user')->user();
            $role = $user->getRole();
            if ($role == 'User') {
                return null; // Allow access
            }
            // If not a User role, logout and deny access
            Auth::guard('user')->logout();
            throw new AccessDeniedHttpException();
        }
        return route("frontend.showLogin");
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = ['user'];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
                return;
            }
        }

        $this->unauthenticated($request, $guards);
    }
}
