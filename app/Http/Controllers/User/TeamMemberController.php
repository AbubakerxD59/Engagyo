<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\Menu;
use App\Services\TeamMemberService;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamMemberController extends BaseController
{
    protected $teamMemberService;

    public function __construct(TeamMemberService $teamMemberService)
    {
        $this->teamMemberService = $teamMemberService;
    }

    /**
     * Get menu items for team member access: database menus (sidebar) + additional menus (navbar-only).
     */
    private function getMenuItems(): array
    {
        $dbMenus = Menu::orderBy('display_order')->get()
            ->map(function ($menu) {
                $menuId = get_team_member_menu_id($menu);
                if (!$menuId) {
                    return null;
                }
                return [
                    'id' => $menuId,
                    'name' => $menu->name,
                    'icon' => $menu->icon,
                    'route' => $menu->route,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        return array_merge($dbMenus, Menu::additionalMenus());
    }

    public function index()
    {
        $user = Auth::guard('user')->user();
        $teamMembers = TeamMember::where('team_lead_id', $user->id)
            ->with('member', 'menus', 'featureLimits.feature', 'accounts')
            ->get();

        return view('user.team-members.index', compact('teamMembers'));
    }

    public function create()
    {
        $user = Auth::guard('user')->user();
        
        // Define menu items from sidebar
        $menuItems = $this->getMenuItems();
        
        // Get available features from team lead's package
        $activePackage = $user->activeUserPackage;
        $features = collect();
        if ($activePackage && $activePackage->package) {
            $features = $activePackage->package->features()
                ->wherePivot('is_enabled', true)
                ->get();
        }
        
        // Get available accounts with full details
        $accounts = collect();
        
        // Facebook Pages
        $pages = $user->pages()->with('facebook')->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'type' => 'page',
                'name' => $page->name,
                'username' => $page->facebook?->username ?? $page->name,
                'profile_image' => $page->profile_image ?? ($page->facebook->profile_image ?? social_logo('facebook')),
                'account_id' => $page->page_id ?? $page->id,
            ];
        });
        
        // Pinterest Boards
        $boards = $user->boards()->with('pinterest')->get()->map(function ($board) {
            return [
                'id' => $board->id,
                'type' => 'board',
                'name' => $board->name,
                'username' => $board->pinterest?->username ?? $board->name,
                'profile_image' => $board->pinterest->profile_image ?? social_logo('pinterest'),
                'account_id' => $board->board_id ?? $board->id,
            ];
        });
        
        // TikTok Accounts
        $tiktoks = $user->tiktok()->get()->map(function ($tiktok) {
            return [
                'id' => $tiktok->id,
                'type' => 'tiktok',
                'name' => $tiktok->display_name ?? $tiktok->username ?? 'TikTok Account',
                'username' => $tiktok->username ?? 'TikTok Account',
                'profile_image' => $tiktok->profile_image ?? social_logo('tiktok'),
                'account_id' => $tiktok->tiktok_id ?? $tiktok->id,
            ];
        });
        
        $accounts = $accounts->concat($pages)->concat($boards)->concat($tiktoks);

        return view('user.team-members.create', compact('menuItems', 'features', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'menu_access' => 'array',
            'feature_limits' => 'array',
            'accounts' => 'array',
        ]);

        $user = Auth::guard('user')->user();
        
        // Get menu access (menu IDs)
        $menus = $request->has('menu_access') ? $request->menu_access : [];
        
        // Process feature limits
        $featureLimits = [];
        if ($request->has('feature_limits')) {
            foreach ($request->feature_limits as $featureId => $limitValue) {
                if ($limitValue !== null && $limitValue !== '') {
                    $featureLimits[$featureId] = [
                        'limit_value' => (int)$limitValue,
                        'is_unlimited' => false,
                    ];
                }
            }
        }
        
        // Process accounts - only include checked ones (those with 'type' set)
        $accounts = [];
        if ($request->has('accounts')) {
            foreach ($request->accounts as $account) {
                if (isset($account['type']) && !empty($account['type']) && isset($account['id'])) {
                    $accounts[] = [
                        'type' => $account['type'],
                        'id' => $account['id'],
                    ];
                }
            }
        }
        
        $result = $this->teamMemberService->inviteTeamMember(
            $user,
            $request->email,
            $menus,
            $featureLimits,
            $accounts
        );

        if ($result['success']) {
            return redirect()->route('panel.team-members.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    public function edit(TeamMember $teamMember)
    {
        // Verify ownership
        if ($teamMember->team_lead_id !== Auth::guard('user')->id()) {
            abort(403);
        }

        $user = Auth::guard('user')->user();
        
        // Get menu items
        $menuItems = $this->getMenuItems();
        
        // Get available features
        $activePackage = $user->activeUserPackage;
        $features = collect();
        if ($activePackage && $activePackage->package) {
            $features = $activePackage->package->features()
                ->wherePivot('is_enabled', true)
                ->get();
        }
        
        // Get available accounts with full details
        $accounts = collect();
        
        // Facebook Pages
        $pages = $user->pages()->with('facebook')->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'type' => 'page',
                'name' => $page->name,
                'username' => $page->facebook?->username ?? $page->name,
                'profile_image' => $page->profile_image ?? ($page->facebook->profile_image ?? social_logo('facebook')),
                'account_id' => $page->page_id ?? $page->id,
            ];
        });
        
        // Pinterest Boards
        $boards = $user->boards()->with('pinterest')->get()->map(function ($board) {
            return [
                'id' => $board->id,
                'type' => 'board',
                'name' => $board->name,
                'username' => $board->pinterest?->username ?? $board->name,
                'profile_image' => $board->pinterest->profile_image ?? social_logo('pinterest'),
                'account_id' => $board->board_id ?? $board->id,
            ];
        });
        
        // TikTok Accounts
        $tiktoks = $user->tiktok()->get()->map(function ($tiktok) {
            return [
                'id' => $tiktok->id,
                'type' => 'tiktok',
                'name' => $tiktok->display_name ?? $tiktok->username ?? 'TikTok Account',
                'username' => $tiktok->username ?? 'TikTok Account',
                'profile_image' => $tiktok->profile_image ?? social_logo('tiktok'),
                'account_id' => $tiktok->tiktok_id ?? $tiktok->id,
            ];
        });
        
        $accounts = $accounts->concat($pages)->concat($boards)->concat($tiktoks);
        
        // Load team member relationships
        $teamMember->load('menus', 'featureLimits', 'accounts');

        return view('user.team-members.edit', compact('teamMember', 'menuItems', 'features', 'accounts'));
    }

    public function update(Request $request, TeamMember $teamMember)
    {
        // Verify ownership
        if ($teamMember->team_lead_id !== Auth::guard('user')->id()) {
            abort(403);
        }

        $request->validate([
            'menu_access' => 'array',
            'feature_limits' => 'array',
            'accounts' => 'array',
        ]);

        // Update menu access
        if ($request->has('menu_access')) {
            $menus = $request->menu_access ?? [];
            $this->teamMemberService->updateMenus($teamMember, $menus);
        }

        if ($request->has('feature_limits')) {
            $featureLimits = [];
            foreach ($request->feature_limits as $featureId => $limitValue) {
                if ($limitValue !== null && $limitValue !== '') {
                    $featureLimits[$featureId] = [
                        'limit_value' => (int)$limitValue,
                        'is_unlimited' => false,
                    ];
                }
            }
            $this->teamMemberService->updateFeatureLimits($teamMember, $featureLimits);
        }

        if ($request->has('accounts')) {
            $accounts = [];
            foreach ($request->accounts as $account) {
                if (isset($account['type']) && !empty($account['type']) && isset($account['id'])) {
                    $accounts[] = [
                        'type' => $account['type'],
                        'id' => $account['id'],
                    ];
                }
            }
            $this->teamMemberService->updateAccountAccess($teamMember, $accounts);
        }

        return redirect()->route('panel.team-members.index')
            ->with('success', 'Team member updated successfully.');
    }

    public function destroy(TeamMember $teamMember)
    {
        if ($teamMember->team_lead_id !== Auth::guard('user')->id()) {
            abort(403);
        }

        $this->teamMemberService->removeTeamMember($teamMember);

        return redirect()->route('panel.team-members.index')
            ->with('success', 'Team member removed successfully.');
    }
}

