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
        if (Auth::check()) {
            $role = Auth::user()->getRole();
            if ($role == 'User') {
                return null;
            }
            Auth::logout();
            throw new AccessDeniedHttpException();
        }
        return route("frontend.showLogin");
    }
}
