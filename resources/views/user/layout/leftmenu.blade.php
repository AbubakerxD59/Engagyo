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
                {{-- Schedule --}}
                <?php
                $schedule = ['panel.schedule'];
                ?>
                <li class="nav-item">
                    <a href="{{ route('panel.schedule') }}"
                        class="nav-link {{ in_array(request()->route()->getName(), $schedule) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar"></i>
                        <p>Schedule</p>
                    </a>
                </li>
                {{-- Automation --}}
                <?php
                $automation = ['panel.automation'];
                ?>
                <li class="nav-item">
                    <a href="{{ route('panel.automation') }}"
                        class="nav-link {{ in_array(request()->route()->getName(), $automation) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-rss"></i>
                        <p>Automation</p>
                    </a>
                </li>
                {{-- Accounts --}}
                <?php
                $account = ['panel.accounts', 'panel.accounts.pinterest', 'panel.accounts.facebook'];
                ?>
                <li class="nav-item">
                    <a href="{{ route('panel.accounts') }}"
                        class="nav-link {{ in_array(request()->route()->getName(), $account) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-circle"></i>
                        <p>Accounts</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
