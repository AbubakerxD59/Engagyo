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
                    // Get account image if available (for Facebook, TikTok, Pinterest notifications)
                    let accountImageHtml = '';
                    if (notification.account_image && notification.social_type) {
                        const imageUrl = escapeHtml(notification.account_image);
                        accountImageHtml =
                            `<img src="${imageUrl}" alt="${notification.social_type || ''}" class="notification-account-image" onerror="this.style.display='none';">`;
                    }

                    // Get notification type from body to determine icon
                    let notificationType = null;
                    let statusIconHtml = '';
                    if (notification.body && typeof notification.body === 'object') {
                        notificationType = notification.body.type || null;
                    }

                    // Add status icon based on notification type
                    if (notificationType === 'success') {
                        statusIconHtml =
                            '<i class="fas fa-check-circle notification-status-icon notification-icon-success"></i>';
                    } else if (notificationType === 'error') {
                        statusIconHtml =
                            '<i class="fas fa-times-circle notification-status-icon notification-icon-error"></i>';
                    } else if (notificationType === 'warning') {
                        statusIconHtml =
                            '<i class="fas fa-exclamation-triangle notification-status-icon notification-icon-warning"></i>';
                    }

                    html += `
                        <div class="${itemClass}" data-id="${notification.id}">
                            <div class="notification-title-wrapper">
                                ${accountImageHtml}
                                <div class="notification-title">${escapeHtml(notification.title)}</div>
                                ${statusIconHtml}
                            </div>
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
