<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TeamMember;
use Symfony\Component\HttpFoundation\Response;

class CheckTeamMemberMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return redirect()->route('frontend.showLogin');
        }

        // If user is not a team member, allow access (team leads have full access)
        if (!$user->isTeamMember()) {
            return $next($request);
        }

        // Get the current route name
        $routeName = $request->route()->getName();

        if (!$routeName) {
            // If route has no name, allow access (might be a resource route or API endpoint)
            return $next($request);
        }

        // Get team member's active membership
        $teamMember = $user->activeTeamMembership();

        if (!$teamMember) {
            // If no active membership, deny access
            abort(403, 'You do not have an active team membership.');
        }

        // Load team member menus
        $teamMember->load('menus');

        // Check if team member has access to this route
        $hasAccess = $this->checkRouteAccess($teamMember, $routeName);

        if (!$hasAccess) {
            // Deny access if no permission
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }

    /**
     * Check if team member has access to the route
     */
    private function checkRouteAccess(TeamMember $teamMember, string $routeName): bool
    {
        // Get all menu items and their route names
        $menuItems = $this->getMenuItems();

        // Find which menu item this route belongs to
        $menuItem = null;
        foreach ($menuItems as $item) {
            if (in_array($routeName, $item['route_names']) || $routeName === $item['route']) {
                $menuItem = $item;
                break;
            }
        }

        // If route doesn't belong to any menu item, allow access (e.g., settings, notifications)
        if (!$menuItem) {
            return true;
        }

        // Check if team member has access to this menu item
        $hasMenuAccess = $teamMember->menus()
            ->where('menu_id', $menuItem['id'])
            ->exists();

        return $hasMenuAccess;
    }

    /**
     * Get menu items array (same as in TeamMemberController)
     */
    private function getMenuItems(): array
    {
        return [
            [
                'id' => 'schedule',
                'name' => 'Schedule',
                'icon' => 'fas fa-calendar',
                'route' => 'panel.schedule',
                'route_names' => ['panel.schedule', 'panel.schedule.account.status', 'panel.schedule.process.post', 'panel.schedule.get.setting', 'panel.schedule.timeslot.setting', 'panel.schedule.posts.listing', 'panel.schedule.post.delete', 'panel.schedule.post.edit', 'panel.schedule.post.update', 'panel.schedule.post.publish.now']
            ],
            [
                'id' => 'automation',
                'name' => 'Automation',
                'icon' => 'fas fa-rss',
                'route' => 'panel.automation',
                'route_names' => ['panel.automation', 'panel.automation.feedUrl', 'panel.automation.getDomain', 'panel.automation.saveFilters', 'panel.automation.deleteDomain', 'panel.automation.posts.dataTable', 'panel.automation.posts.destroy', 'panel.automation.posts.update', 'panel.automation.posts.publish', 'panel.automation.posts.shuffle', 'panel.automation.posts.deleteAll', 'panel.automation.posts.fix']
            ],
            [
                'id' => 'api-posts',
                'name' => 'API Posts',
                'icon' => 'fas fa-code',
                'route' => 'panel.api-posts',
                'route_names' => ['panel.api-posts', 'panel.api-posts.posts.listing', 'panel.api-posts.post.delete', 'panel.api-posts.post.edit', 'panel.api-posts.post.update', 'panel.api-posts.post.publish.now']
            ],
            [
                'id' => 'accounts',
                'name' => 'Accounts',
                'icon' => 'fas fa-user-circle',
                'route' => 'panel.accounts',
                'route_names' => ['panel.accounts', 'panel.accounts.pinterest', 'panel.accounts.facebook', 'panel.accounts.tiktok', 'panel.accounts.addBoard', 'panel.accounts.pinterest.delete', 'panel.accounts.board.delete', 'panel.accounts.addPage', 'panel.accounts.facebook.delete', 'panel.accounts.page.delete', 'panel.accounts.tiktok.delete', 'panel.accounts.toggleRssPause']
            ],
            [
                'id' => 'team',
                'name' => 'Team',
                'icon' => 'fas fa-users',
                'route' => 'panel.team-members.index',
                'route_names' => ['panel.team-members.index', 'panel.team-members.create', 'panel.team-members.edit', 'panel.team-members.update', 'panel.team-members.destroy', 'panel.team-members.store']
            ],
        ];
    }
}

