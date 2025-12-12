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
                            : default_user_avatar($user->id, $user->full_name);
                @endphp
                <img src="{{ $profilePic }}" alt="User Image" class="user-nav-image rounded-circle" width="32px"
                    height="32px"
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

    /* Notifications Styles */
    .notifications-dropdown {
        position: relative;
    }

    .notifications-dropdown .nav-link {
        padding: 12px !important;
        position: relative;
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .notifications-dropdown .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #fff !important;
    }

    .notification-badge {
        position: absolute;
        top: 4px;
        right: 4px;
        font-size: 10px;
        padding: 2px 5px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }

    .notifications-menu {
        width: 380px;
        height: 50vh;
        max-height: 60vh;
        overflow: hidden;
        padding: 0;
        display: none;
        flex-direction: column;
    }

    #notificationsDropdown {
        line-height: 0px;
    }

    .notifications-menu.show {
        display: flex !important;
    }

    .notifications-menu .dropdown-header {
        background-color: #f8f9fa;
        font-weight: 600;
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
        flex-shrink: 0;
    }

    .notifications-list {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        min-height: 0;
    }

    .notification-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .notification-item:hover {
        background-color: #f8f9fa;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    /* Unread notification styling */
    .notification-item.unread {
        background-color: #e7f3ff;
        border-left: 3px solid #007bff;
    }

    .notification-item.unread:hover {
        background-color: #d0e7ff;
    }

    /* Read notification styling */
    .notification-item.read {
        background-color: #ffffff;
        border-left: 3px solid transparent;
        opacity: 0.85;
    }

    .notification-item.read:hover {
        background-color: #f8f9fa;
        opacity: 1;
    }

    .notification-item.read .notification-title {
        color: #6c757d;
        font-weight: 500;
    }

    .notification-item.read .notification-body {
        color: #999;
    }

    .notification-item.read .notification-time {
        color: #bbb;
    }

    .notification-title {
        font-weight: 600;
        font-size: 14px;
        color: #333;
        margin-bottom: 4px;
    }

    .notification-body {
        font-size: 13px;
        color: #666;
        margin-bottom: 4px;
        line-height: 1.4;
    }

    .notification-icon {
        color: black;
        font-size: 20px !important;

    }

    .notification-time {
        font-size: 11px;
        color: #999;
    }

    .notification-system {
        border-left-color: #28a745 !important;
    }

    .notification-system .notification-title::before {
        content: "🔔 ";
    }

    .no-notifications {
        padding: 30px 15px;
        text-align: center;
        color: #999;
    }

    .no-notifications i {
        font-size: 48px;
        margin-bottom: 10px;
        opacity: 0.5;
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

        // Notifications System
        let notificationRefreshInterval;
        const NOTIFICATION_REFRESH_INTERVAL = 5000; // 5 seconds

        // Handle dropdown toggle - prevent Bootstrap's default behavior
        $('#notificationsDropdown').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $menu = $('.notifications-menu');
            const isExpanded = $(this).attr('aria-expanded') === 'true';

            if (isExpanded) {
                // Hide menu
                $menu.removeClass('show').fadeOut(200);
                $(this).attr('aria-expanded', 'false');
            } else {
                // Show menu
                $menu.addClass('show').fadeIn(200);
                $(this).attr('aria-expanded', 'true');
                // Fetch notifications when opening
                fetchNotifications();
            }
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.notifications-dropdown').length) {
                $('.notifications-menu').removeClass('show').fadeOut(200);
                $('#notificationsDropdown').attr('aria-expanded', 'false');
            }
        });

        function fetchNotifications() {
            $.ajax({
                url: '{{ route('panel.notifications.fetch') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateNotificationsUI(response.notifications, response.count);
                    }
                },
                error: function(xhr) {
                    console.error('Failed to fetch notifications:', xhr);
                }
            });
        }

        function updateNotificationsUI(notifications, count) {
            const $badge = $('#notificationBadge');
            const $list = $('#notificationsList');
            const $markAllBtn = $('#markAllReadBtn');

            // Update badge
            if (count > 0) {
                $badge.text(count > 99 ? '99+' : count).show();
            } else {
                $badge.hide();
            }

            // Update list
            if (notifications.length === 0) {
                $list.html(`
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <p>No new notifications</p>
                    </div>
                `);
                $markAllBtn.hide();
            } else {
                let html = '';
                notifications.forEach(function(notification) {
                    const isSystem = notification.is_system;
                    const isRead = notification.is_read;
                    let itemClass = 'notification-item';
                    if (isSystem) {
                        itemClass += ' notification-system';
                    }
                    if (isRead) {
                        itemClass += ' read';
                    } else {
                        itemClass += ' unread';
                    }
                    html += `
                        <div class="${itemClass}" data-id="${notification.id}">
                            <div class="notification-title">${escapeHtml(notification.title)}</div>
                            <div class="notification-body">${formatNotificationBody(notification.body)}</div>
                            <div class="notification-time">${notification.created_at}</div>
                        </div>
                    `;
                });
                $list.html(html);

                // Show mark all button only if there are unread notifications
                if (count > 0) {
                    $markAllBtn.show();
                } else {
                    $markAllBtn.hide();
                }

                // Add click handlers
                $('.notification-item').on('click', function() {
                    const notificationId = $(this).data('id');
                    const $item = $(this);
                    if (!$item.hasClass('read')) {
                        markNotificationAsRead(notificationId);
                    }
                });
            }
        }

        function markNotificationAsRead(notificationId) {
            $.ajax({
                url: '{{ route('panel.notifications.markRead', ':id') }}'.replace(':id',
                    notificationId),
                method: 'POST',
                success: function(response) {
                    if (response.success) {
                        // Update the notification styling to read
                        const $item = $(`.notification-item[data-id="${notificationId}"]`);
                        $item.removeClass('unread').addClass('read');
                        // Refresh count
                        fetchNotifications();
                    }
                },
                error: function(xhr) {
                    console.error('Failed to mark notification as read:', xhr);
                }
            });
        }

        function markAllAsRead() {
            $.ajax({
                url: '{{ route('panel.notifications.markAllRead') }}',
                method: 'POST',
                success: function(response) {
                    if (response.success) {
                        fetchNotifications();
                    }
                },
                error: function(xhr) {
                    console.error('Failed to mark all notifications as read:', xhr);
                }
            });
        }

        function formatNotificationBody(body) {
            if (!body) return '';
            if (typeof body === 'string') {
                return escapeHtml(body);
            }
            if (typeof body === 'object') {
                // If body is an object, try to extract message
                if (body.message) {
                    return escapeHtml(body.message);
                }
                if (body.text) {
                    return escapeHtml(body.text);
                }
                return escapeHtml(JSON.stringify(body));
            }
            return '';
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        }

        // Mark all as read button
        $(document).on('click', '#markAllReadBtn', function(e) {
            e.preventDefault();
            markAllAsRead();
        });

        // Initial fetch
        fetchNotifications();

        // Set up auto-refresh
        notificationRefreshInterval = setInterval(fetchNotifications, NOTIFICATION_REFRESH_INTERVAL);

        // Clear interval when page is hidden (to save resources)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                if (notificationRefreshInterval) {
                    clearInterval(notificationRefreshInterval);
                }
            } else {
                // Restart when page becomes visible
                fetchNotifications();
                notificationRefreshInterval = setInterval(fetchNotifications,
                    NOTIFICATION_REFRESH_INTERVAL);
            }
        });
    });
</script>
