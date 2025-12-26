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
                {{-- Users --}}
                @can('view_user')
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), ['admin.users.index', 'admin.users.create', 'admin.users.edit']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Users</p>
                        </a>
                    </li>
                @endcan
                {{-- Packages --}}
                @can('view_package')
                    <li class="nav-item">
                        <a href="{{ route('admin.packages.index') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), ['admin.packages.index', 'admin.packages.create', 'admin.packages.edit']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>Packages</p>
                        </a>
                    </li>
                @endcan
                {{-- Features --}}
                @can('view_feature')
                    <li class="nav-item">
                        <a href="{{ route('admin.features.index') }}"
                            class="nav-link {{ request()->route()->getName() == 'admin.features.index' ? 'active' : '' }}">
                            <i class="nav-icon fas fa-star"></i>
                            <p>Features</p>
                        </a>
                    </li>
                @endcan
                {{-- Promo Codes --}}
                @can('view_promocode')
                    <li class="nav-item">
                        <a href="{{ route('admin.promo-codes.index') }}"
                            class="nav-link {{ in_array(request()->route()->getName(), ['admin.promo-codes.index', 'admin.promo-codes.create', 'admin.promo-codes.edit']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tag"></i>
                            <p>Promo Codes</p>
                        </a>
                    </li>
                @endcan
                {{-- Test Cases --}}
                <li
                    class="nav-item has-treeview {{ in_array(request()->route()->getName(), ['admin.facebook-tests.index', 'admin.facebook-tests.show', 'admin.pinterest-tests.index', 'admin.pinterest-tests.show', 'admin.tiktok-tests.index', 'admin.tiktok-tests.show']) ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ in_array(request()->route()->getName(), ['admin.facebook-tests.index', 'admin.facebook-tests.show', 'admin.pinterest-tests.index', 'admin.pinterest-tests.show', 'admin.tiktok-tests.index', 'admin.tiktok-tests.show']) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-vial"></i>
                        <p>
                            Test Cases
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.facebook-tests.index') }}"
                                class="nav-link {{ in_array(request()->route()->getName(), ['admin.facebook-tests.index', 'admin.facebook-tests.show']) ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Facebook</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.pinterest-tests.index') }}"
                                class="nav-link {{ in_array(request()->route()->getName(), ['admin.pinterest-tests.index', 'admin.pinterest-tests.show']) ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pinterest</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.tiktok-tests.index') }}"
                                class="nav-link {{ in_array(request()->route()->getName(), ['admin.tiktok-tests.index', 'admin.tiktok-tests.show']) ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>TikTok</p>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>
