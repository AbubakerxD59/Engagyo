<nav class="main-header navbar navbar-expand-md">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
                <i class="fa fa-times"></i>
            </a>
        </li>
    </ul>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Notifications Dropdown -->
        <li class="nav-item dropdown notifications-dropdown">
            <a class="nav-link" href="#" id="notificationsDropdown" aria-haspopup="true" aria-expanded="false"
                role="button">
                <i class="fas fa-bell notification-icon"></i>
                <span class="badge badge-danger notification-badge" id="notificationBadge"
                    style="display: none;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notifications-menu"
                aria-labelledby="notificationsDropdown">
                <span class="dropdown-item dropdown-header">Notifications</span>
                <div class="dropdown-divider"></div>
                <div id="notificationsList" class="notifications-list">
                    <div class="dropdown-item text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin"></i> Loading notifications...
                    </div>
                </div>
                <div class="dropdown-divider" style="flex-shrink: 0;"></div>
                <a href="#" class="dropdown-item dropdown-footer" id="markAllReadBtn"
                    style="display: none; flex-shrink: 0; padding: 10px 15px; text-align: center; background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <i class="fas fa-check-double mr-2"></i> Mark all as read
                </a>
            </div>
        </li>
        <!-- User Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle user-dropdown-toggle" href="#" id="userDropdown"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                @php
                    $user = auth()->user();
                    $rawProfilePic = $user->getAttributes()['profile_pic'] ?? null;
                    $profilePic =
                        !empty($rawProfilePic) && file_exists(public_path($rawProfilePic))
                            ? asset($rawProfilePic)
                            : default_user_avatar($user->id, $user->username);
                @endphp
                <img src="{{ $profilePic }}" alt="User Image" class="user-nav-image rounded-circle" width="32px"
                    height="32px"
                    onerror="this.onerror=null; this.src='{{ default_user_avatar($user->id, $user->username) }}';" loading="lazy">
                <span class="user-nav-name text-muted">{{ auth()->user()->username }}</span>
                <i class="fas fa-chevron-down user-nav-arrow text-muted"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right user-dropdown-menu" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="{{ route('panel.settings') }}">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <a class="dropdown-item" href="{{ route('panel.plan.billing') }}">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Plan & Billing
                </a>
                @if (!$user->isTeamMember() || $user->hasMenuAccess('api'))
                <a class="dropdown-item" href="{{ route('panel.api-keys') }}">
                    <i class="fas fa-key mr-2"></i> API Keys
                </a>
                @endif
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger logout-btn" href="#">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
                <form action="{{ route('frontend.logout') }}" method="GET" id="logout_form" style="display: none;">
                </form>
            </div>
        </li>
    </ul>
</nav>

<style>
    /* Ensure navbar appears above feature-limit-alert */
    /* nav.main-header.navbar,
    .main-header.navbar {
        z-index: 1060 !important;
        position: relative;
    } */

    /* Ensure dropdown parent items have proper positioning context */
    .main-header .nav-item.dropdown {
        position: relative;
        z-index: 1061;
    }

    /* Ensure notification dropdown appears above feature-limit-alert */
    .notifications-dropdown .dropdown-menu.notifications-menu,
    .notifications-dropdown .dropdown-menu {
        z-index: 1062 !important;
    }

    /* Ensure user profile dropdown appears above feature-limit-alert */
    .user-dropdown-menu,
    .nav-item.dropdown .user-dropdown-menu {
        z-index: 1062 !important;
    }

    /* Ensure all dropdown menus in navbar appear above feature-limit-alert */
    .main-header .dropdown-menu {
        z-index: 1062 !important;
    }
</style>
