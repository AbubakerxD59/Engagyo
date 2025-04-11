<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="" class="brand-link d-flex justify-content-center">
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
                <form action="{{ route('frontend.logout') }}" method="GET" id="signout_form">
                </form>
                <a class="sign-out pointer">Logout</a>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column py-2" data-widget="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('panel.accounts') }}"
                        class="nav-link {{ request()->route()->getName() == 'panel.accounts' ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-circle"></i>
                        <p>Accounts</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
