<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('panel.schedule') }}" class="brand-link d-flex justify-content-center">
        <img src="{{ panel_logo() }}" alt="{{ env('APP_NAME', 'Engagyo') }}" class="brand-image" style="width: 100px;">
    </a>
    <!-- Sidebar -->
    <div class="sidebar">
        <nav class="mt-3">
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
                {{-- API Posts --}}
                <?php
                $apiPosts = ['panel.api-posts'];
                ?>
                <li class="nav-item">
                    <a href="{{ route('panel.api-posts') }}"
                        class="nav-link {{ in_array(request()->route()->getName(), $apiPosts) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-code"></i>
                        <p>API Posts</p>
                    </a>
                </li>
                {{-- Accounts --}}
                <?php
                $account = ['panel.accounts', 'panel.accounts.pinterest', 'panel.accounts.facebook'];
                ?>
                <li class="nav-item">
                    @canUseFeature('social_accounts')
                    <a href="{{ route('panel.accounts') }}"
                        class="nav-link {{ in_array(request()->route()->getName(), $account) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-circle"></i>
                        <p>Accounts</p>
                    </a>
                @else
                    <a href="#" class="nav-link disabled" style="opacity: 0.5; cursor: not-allowed;"
                        onclick="event.preventDefault(); return false;"
                        title="This feature is not available in your package">
                        <i class="nav-icon fas fa-user-circle"></i>
                        <p>
                            Accounts
                            <i class="fas fa-lock float-right mt-1" style="font-size: 0.8rem;"></i>
                        </p>
                    </a>
                    @endcanUseFeature
                </li>
            </ul>
        </nav>
    </div>
</aside>
