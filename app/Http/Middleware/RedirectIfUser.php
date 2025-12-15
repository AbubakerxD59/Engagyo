<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Providers\RouteServiceProvider;

/**
 * Redirect user guard users away from admin panel routes
 * If user is logged in with user guard, redirect to user panel
 */
class RedirectIfUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('user')->check()) {
            return redirect(RouteServiceProvider::FRONTEND_AUTH_HOME);
        }

        return $next($request);
    }
}

