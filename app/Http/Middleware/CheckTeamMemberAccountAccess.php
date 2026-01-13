<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TeamMemberAccount;

class CheckTeamMemberAccountAccess
{
    public function handle(Request $request, Closure $next, string $accountType)
    {
        $user = auth()->guard('user')->user();
        
        // If user is not a team member, allow access (they own the accounts)
        if (!$user->isTeamMember()) {
            return $next($request);
        }

        $membership = $user->activeTeamMembership();
        if (!$membership) {
            abort(403, 'You do not have access to this account.');
        }

        // Get account ID from route parameter
        $accountId = $request->route('id') ?? $request->input('account_id');
        
        if (!$accountId) {
            return $next($request);
        }

        // Check if team member has access to this account
        $hasAccess = TeamMemberAccount::where('team_member_id', $membership->id)
            ->where('account_type', $accountType)
            ->where('account_id', $accountId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have access to this account.');
        }

        return $next($request);
    }
}

