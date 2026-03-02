<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    /**
     * Handle an incoming request.
     * Sets the default timezone for the request based on the authenticated user's timezone.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('user')->user();

        $timezoneName = 'America/New_York'; // Default when no timezone is selected

        if ($user && $user->timezone_id) {
            $timezone = $user->timezone;
            if ($timezone && !empty($timezone->name)) {
                $timezoneName = $timezone->name;
            }
        }

        date_default_timezone_set($timezoneName);
        config(['app.timezone' => $timezoneName]);

        return $next($request);
    }
}
