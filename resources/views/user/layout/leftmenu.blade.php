<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('panel.schedule') }}" class="brand-link d-flex justify-content-center">
        <img src="{{ panel_logo() }}" alt="{{ env('APP_NAME', 'Engagyo') }}" class="brand-image" style="width: 100px;">
    </a>
    <!-- Sidebar -->
    <div class="sidebar">
        <nav class="mt-3">
            @php
                $user = auth()->guard('user')->user();
                $menus = get_menus();
            @endphp

            <ul class="nav nav-pills nav-sidebar flex-column py-2" data-widget="treeview" role="menu"
                data-accordion="false">
                @foreach ($menus as $menu)
                    @php
                        $teamMemberMenuId = get_team_member_menu_id($menu);
                        $showMenu = !$user->isTeamMember() || ($teamMemberMenuId && $user->hasMenuAccess($teamMemberMenuId));
                    @endphp
                    @if ($showMenu)
                    <li class="nav-item">
                        @canAccessMenu($menu->id)
                        <a href="{{ route($menu->route) }}"
                            class="nav-link {{ in_array(request()->route()->getName(), [$menu->route]) ? 'active' : '' }}">
                            <i class="nav-icon {{ $menu->icon }}"></i>
                            <p>{{ $menu->name }}</p>
                        </a>
                    @else
                        <a href="#"
                            class="nav-link disabled {{ in_array(request()->route()->getName(), [$menu->route]) ? 'active' : '' }}">
                            <i class="nav-icon {{ $menu->icon }}"></i>
                            <p>
                                {{ $menu->name }}
                                <i class="fas fa-lock float-right mt-1" style="font-size: 0.8rem;"></i>
                            </p>
                        </a>
                        @endcanAccessMenu
                    </li>
                    @endif
                @endforeach
                {{-- Schedule --}}
                {{-- @php
                    $schedule = ['panel.schedule'];
                    $hasScheduleAccess = !$user->isTeamMember() || $user->hasMenuAccess('schedule');
                @endphp
                @if ($hasScheduleAccess)
                    <li class="nav-item">
                        <a href="{{ route('panel.schedule') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), $schedule) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar"></i>
                            @canUseFeature(\App\Models\Feature::$features_list[1])
                            <p>Schedule</p>
                            @elsecanUseFeature(\App\Models\Feature::$features_list[1])
                            <p>
                                Schedule
                                <i class="fas fa-lock float-right mt-1" style="font-size: 0.8rem;"></i>
                            </p>
                            @endcanUseFeature
                        </a>
                    </li>
                @endif --}}
                {{-- Automation --}}
                {{-- @php
                    $automation = ['panel.automation'];
                    $hasAutomationAccess = !$user->isTeamMember() || $user->hasMenuAccess('automation');
                @endphp
                @if ($hasAutomationAccess)
                    <li class="nav-item">
                        <a href="{{ route('panel.automation') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), $automation) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-rss"></i>
                            @canUseFeature(\App\Models\Feature::$features_list[2])
                            <p>Automation</p>
                            @elsecanUseFeature(\App\Models\Feature::$features_list[2])
                            <p>
                                Automation
                                <i class="fas fa-lock float-right mt-1" style="font-size: 0.8rem;"></i>
                            </p>
                            @endcanUseFeature
                        </a>
                    </li>
                @endif --}}
                {{-- API Posts --}}
                {{-- @php
                    $apiPosts = ['panel.api-posts'];
                    $hasApiPostsAccess = !$user->isTeamMember() || $user->hasMenuAccess('api-posts');
                @endphp
                @if ($hasApiPostsAccess)
                    <li class="nav-item">
                        <a href="{{ route('panel.api-posts') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), $apiPosts) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-code"></i>
                            @canUseFeature(\App\Models\Feature::$features_list[5])
                            <p>API Posts</p>
                            @elsecanUseFeature(\App\Models\Feature::$features_list[5])
                            <p>
                                API Posts
                                <i class="fas fa-lock float-right mt-1" style="font-size: 0.8rem;"></i>
                            </p>
                            @endcanUseFeature
                        </a>
                    </li>
                @endif --}}
                {{-- Accounts --}}
                {{-- @php
                    $account = ['panel.accounts', 'panel.accounts.pinterest', 'panel.accounts.facebook'];
                    $hasAccountsAccess = !$user->isTeamMember() || $user->hasMenuAccess('accounts');
                @endphp
                @if ($hasAccountsAccess)
                    <li class="nav-item">
                        <a href="{{ route('panel.accounts') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), $account) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-circle"></i>
                            @canUseFeature(\App\Models\Feature::$features_list[0])
                            <p>Accounts</p>
                            @elsecanUseFeature(\App\Models\Feature::$features_list[0])
                            <p>
                                Accounts
                                <i class="fas fa-lock float-right mt-1" style="font-size: 0.8rem;"></i>
                            </p>
                            @endcanUseFeature
                        </a>
                    </li>
                @endif --}}
                {{-- Team --}}
                {{-- @php
                    $teamMembers = ['panel.team-members.index', 'panel.team-members.create', 'panel.team-members.edit'];
                    $hasTeamAccess = !$user->isTeamMember() || $user->hasMenuAccess('team');
                @endphp
                @if ($hasTeamAccess)
                    <li class="nav-item">
                        <a href="{{ route('panel.team-members.index') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), $teamMembers) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Team</p>
                        </a>
                    </li>
                @endif --}}
            </ul>
        </nav>
    </div>
</aside>
