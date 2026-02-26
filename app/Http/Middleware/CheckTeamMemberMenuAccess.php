<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TeamMember;
use App\Models\User;
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
        $user = User::where('id', Auth::guard('user')->user()->id)->first();

        if (!$user) {
            return redirect()->route('frontend.showLogin');
        }

        // If user has no team membership, they are the account owner (team leader) — allow full access
        if (!$user->isTeamMember()) {
            return $next($request);
        }

        // Get the current route name
        $routeName = $request->route()->getName();

        if (!$routeName) {
            // If route has no name, allow access (might be a resource route or API endpoint)
            return $next($request);
        }

        // Get team member's active membership (already confirmed above)
        $teamMember = $user->activeTeamMembership();

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
     * Get menu items array with current user panel routes.
     */
    private function getMenuItems(): array
    {
        return [
            [
                'id' => 'schedule',
                'name' => 'Schedule',
                'icon' => 'fas fa-calendar',
                'route' => 'panel.schedule',
                'route_names' => [
                    'panel.schedule',
                    'panel.schedule.account.status',
                    'panel.schedule.process.post',
                    'panel.schedule.get.setting',
                    'panel.schedule.timeslot.setting',
                    'panel.schedule.timeslot.setting.save',
                    'panel.schedule.posts.listing',
                    'panel.schedule.post.delete',
                    'panel.schedule.post.edit',
                    'panel.schedule.post.update',
                    'panel.schedule.post.publish.now',
                ],
            ],
            [
                'id' => 'automation',
                'name' => 'Automation',
                'icon' => 'fas fa-rss',
                'route' => 'panel.automation',
                'route_names' => [
                    'panel.automation',
                    'panel.automation.feedUrl',
                    'panel.automation.getDomain',
                    'panel.automation.saveFilters',
                    'panel.automation.deleteDomain',
                    'panel.automation.posts.dataTable',
                    'panel.automation.posts.destroy',
                    'panel.automation.posts.update',
                    'panel.automation.posts.publish',
                    'panel.automation.posts.shuffle',
                    'panel.automation.posts.deleteAll',
                    'panel.automation.posts.fix',
                    'panel.automation.posts.saveChanges',
                ],
            ],
            [
                'id' => 'api-posts',
                'name' => 'API Posts',
                'icon' => 'fas fa-code',
                'route' => 'panel.api-posts',
                'route_names' => [
                    'panel.api-posts',
                    'panel.api-posts.posts.listing',
                    'panel.api-posts.post.delete',
                    'panel.api-posts.post.edit',
                    'panel.api-posts.post.update',
                    'panel.api-posts.post.publish.now',
                ],
            ],
            [
                'id' => 'accounts',
                'name' => 'Accounts',
                'icon' => 'fas fa-user-circle',
                'route' => 'panel.accounts',
                'route_names' => [
                    'panel.accounts',
                    'panel.accounts.pinterest',
                    'panel.accounts.addBoard',
                    'panel.accounts.pinterest.delete',
                    'panel.accounts.board.delete',
                    'panel.accounts.facebook',
                    'panel.accounts.facebook.socialite',
                    'panel.accounts.addPage',
                    'panel.accounts.facebook.delete',
                    'panel.accounts.page.delete',
                    'panel.accounts.tiktok',
                    'panel.accounts.tiktok.delete',
                    'panel.accounts.toggleRssPause',
                ],
            ],
            [
                'id' => 'team',
                'name' => 'Team',
                'icon' => 'fas fa-users',
                'route' => 'panel.team-members.index',
                'route_names' => [
                    'panel.team-members.index',
                    'panel.team-members.create',
                    'panel.team-members.store',
                    'panel.team-members.edit',
                    'panel.team-members.update',
                    'panel.team-members.destroy',
                ],
            ],
            [
                'id' => 'api',
                'name' => 'API Access',
                'icon' => 'fas fa-key',
                'route' => 'panel.api-keys',
                'route_names' => [
                    'panel.api-keys',
                    'panel.api-keys.store',
                    'panel.api-keys.refresh',
                    'panel.api-keys.toggle',
                    'panel.api-keys.destroy',
                ],
            ],
            [
                'id' => 'url-tracking',
                'name' => 'URL Tracking',
                'icon' => 'fas fa-link',
                'route' => 'panel.url-tracking',
                'route_names' => [
                    'panel.url-tracking',
                    'panel.url-tracking.store',
                    'panel.url-tracking.show',
                    'panel.url-tracking.update',
                    'panel.url-tracking.destroy',
                    'panel.url-tracking.deleteAllDomain',
                    'panel.url-tracking.getByDomain',
                ],
            ],
            [
                'id' => 'link-shortener',
                'name' => 'Link Shortener',
                'icon' => 'fas fa-compress-alt',
                'route' => 'panel.link-shortener',
                'route_names' => [
                    'panel.link-shortener',
                    'panel.link-shortener.account.urlShortenerStatus',
                    'panel.link-shortener.account.urlShortenerStatusBulk',
                    'panel.link-shortener.platform.urlShortenerStatus',
                    'panel.link-shortener.store',
                    'panel.link-shortener.update',
                    'panel.link-shortener.destroy',
                ],
            ],
            [
                'id' => 'analytics',
                'name' => 'Analytics',
                'icon' => 'fas fa-chart-line',
                'route' => 'panel.analytics',
                'route_names' => [
                    'panel.analytics',
                    'panel.analytics.data',
                    'panel.analytics.test',
                ],
            ],
        ];
    }
}
