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
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle user-dropdown-toggle" href="#" id="userDropdown"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                @php
                    $user = auth()->user();
                    $rawProfilePic = $user->getAttributes()['profile_pic'] ?? null;
                    $profilePic = !empty($rawProfilePic) && file_exists(public_path($rawProfilePic)) 
                        ? asset($rawProfilePic) 
                        : default_user_avatar($user->id, $user->full_name);
                @endphp
                <img src="{{ $profilePic }}" alt="User Image"
                    class="user-nav-image rounded-circle" width="32px" height="32px"
                    onerror="this.onerror=null; this.src='{{ default_user_avatar($user->id, $user->full_name) }}';">
                <span class="user-nav-name text-muted">{{ auth()->user()->full_name }}</span>
                <i class="fas fa-chevron-down user-nav-arrow text-muted"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right user-dropdown-menu" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="{{ route('panel.settings') }}">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <a class="dropdown-item" href="{{ route('panel.api-keys') }}">
                    <i class="fas fa-key mr-2"></i> API Keys
                </a>
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
    .user-dropdown-toggle {
        padding: 6px 12px !important;
    }

    .user-dropdown-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
    }

    .user-dropdown-toggle::after {
        display: none;
    }

    .user-nav-panel {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-nav-image {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
    }

    .user-nav-image img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        display: block;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .user-nav-name {
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        line-height: 1.2;
    }

    .user-nav-arrow {
        font-size: 10px;
        color: rgba(255, 255, 255, 0.5);
        margin-left: 2px;
    }

    .user-dropdown-menu {
        min-width: 180px;
        padding: 8px 0;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        margin-top: 8px;
    }

    .user-dropdown-menu .dropdown-item {
        padding: 10px 20px;
        font-size: 14px;
        transition: all 0.2s;
    }

    .user-dropdown-menu .dropdown-item:hover {
        background: #f8f9fa;
    }

    .user-dropdown-menu .dropdown-item i {
        width: 20px;
    }

    .user-dropdown-menu .dropdown-divider {
        margin: 4px 0;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logout button click
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logout_form').submit();
            }
        });
    });
</script>
