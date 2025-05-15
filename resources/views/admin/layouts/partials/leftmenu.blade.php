<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link d-flex justify-content-center">
        <img src="{{ panel_logo() }}" alt="{{ env('APP_NAME', 'Engagyo') }}" class="brand-image" style="width: 100px;">
    </a>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel py-3 d-flex">
            <div class="image off">
                <!-- <img src="/assets/img/user-icon.png" class="userimg" alt="User Image"> -->
                <i class="nav-icon fas fa-power-off"></i>
            </div>
            <div class="info">
                <form action="{{ route('admin.logout') }}" method="POST" id="signout_form">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                </form>
                <a class="sign-out pointer">Logout</a>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column py-2" data-widget="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}"
                        class="nav-link {{ request()->route()->getName() == 'admin.dashboard' ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>{{ __('partials.left_menu_dashboard') }}</p>
                    </a>
                </li>
                {{-- Manage Users --}}
                @if (check_permission('view_user') || check_permission('view_role') || check_permission('view_permission'))
                    <?php
                    $user = in_array(request()->route()->getName(), ['admin.users.index', 'admin.users.create', 'admin.users.edit', 'admin.roles.index', 'admin.roles.create', 'admin.roles.edit', 'admin.permissions.index']) ? true : false;
                    ?>
                    <li class="nav-item {{ $user ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $user ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user"></i>
                            <p>
                                USERS
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                @can('view_user')
                                    <a href="{{ route('admin.users.index') }}"
                                        class="nav-link {{ in_array(request()->route()->getName(), ['admin.users.index', 'admin.users.create', 'admin.users.edit']) ? 'active' : '' }}">
                                        <i class="nav-icon far fa-dot-circle"></i>
                                        <p>Users</p>
                                    </a>
                                @endcan
                                @can('view_role')
                                    <a href="{{ route('admin.roles.index') }}"
                                        class="nav-link {{ in_array(request()->route()->getName(), ['admin.roles.index', 'admin.roles.create', 'admin.roles.edit']) ? 'active' : '' }}">
                                        <i class="nav-icon far fa-dot-circle"></i>
                                        <p>Roles</p>
                                    </a>
                                @endcan
                                @can('view_permission')
                                    <a href="{{ route('admin.permissions.index') }}"
                                        class="nav-link {{ in_array(request()->route()->getName(), ['admin.permissions.index']) ? 'active' : '' }}">
                                        <i class="nav-icon far fa-dot-circle"></i>
                                        <p>Permissions</p>
                                    </a>
                                @endcan
                            </li>
                        </ul>
                    </li>
                @endif
                {{-- Manage Packages --}}
                {{-- @if (check_permission('view_package') || check_permission('view_feature'))
                    <?php
                    $package = in_array(request()->route()->getName(), ['packages.index', 'packages.create', 'packages.edit', 'features.index', 'features.create', 'features.edit', 'promo-code.index', 'promo-code.create', 'promo-code.edit']) ? true : false;
                    ?>
                    <li class="nav-item {{ $package ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $package ? 'active' : '' }}">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>
                                PACKAGES
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                @can('view_package')
                                    <a href="{{ route('admin.packages.index') }}"
                                        class="nav-link {{ in_array(request()->route()->getName(), ['packages.index', 'packages.create', 'packages.edit']) ? 'active' : '' }}">
                                        <i class="nav-icon far fa-dot-circle"></i>
                                        <p>Packages</p>
                                    </a>
                                @endcan
                                @can('view_feature')
                                    <a href="{{ route('admin.features.index') }}"
                                        class="nav-link {{ in_array(request()->route()->getName(), ['features.index', 'features.create', 'features.edit']) ? 'active' : '' }}">
                                        <i class="nav-icon far fa-dot-circle"></i>
                                        <p>Services</p>
                                    </a>
                                @endcan
                                <a href="{{ route('admin.promo-code.index') }}"
                                    class="nav-link {{ in_array(request()->route()->getName(), ['promo-code.index', 'promo-code.create', 'promo-code.edit']) ? 'active' : '' }}">
                                    <i class="nav-icon far fa-dot-circle"></i>
                                    <p>Promo Codes</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif --}}
            </ul>
        </nav>
    </div>
</aside>
