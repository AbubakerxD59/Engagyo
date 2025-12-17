@if (isset($featureLimitReached) && $featureLimitReached)
    <div class="feature-limit-alert-container">
        <div class="feature-limit-banner">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12">
                        <div class="feature-limit-content">
                            <div class="feature-limit-icon-wrapper">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="feature-limit-text">
                                <h4 class="feature-limit-title">
                                    <strong>Feature Limit Reached</strong>
                                </h4>
                                <p class="feature-limit-message">
                                    {{ $featureLimitMessage ?? 'You have reached your feature limit. Upgrade your package to continue using this feature.' }}
                                </p>
                                @if (isset($featureLimitStats) && $featureLimitStats['limit'] !== null)
                                    <div class="feature-limit-stats">
                                        <span class="usage-badge">
                                            <i class="fas fa-chart-line mr-1"></i>
                                            Usage: <strong>{{ $featureLimitStats['current_usage'] }}</strong> /
                                            <strong>{{ $featureLimitStats['limit'] }}</strong>
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="feature-limit-action">
                                <a href="{{ route('frontend.pricing') ?? '#' }}" class="btn btn-upgrade">
                                    <i class="fas fa-arrow-up mr-2"></i>
                                    <span>Upgrade Now</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="feature-limit-overlay"></div>
@endif

<style>
    .feature-limit-alert-container {
        position: relative;
        z-index: 1051;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .feature-limit-banner {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #333;
        padding: 20px 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-bottom: 3px solid #f57c00;
    }

    .feature-limit-content {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .feature-limit-icon-wrapper {
        flex-shrink: 0;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .feature-limit-icon-wrapper i {
        font-size: 24px;
        color: #fff;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .feature-limit-text {
        flex: 1;
        min-width: 0;
    }

    .feature-limit-title {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        margin: 0 0 8px 0;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        letter-spacing: 0.3px;
    }

    .feature-limit-message {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.95);
        margin: 0 0 10px 0;
        line-height: 1.6;
        word-wrap: break-word;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .feature-limit-stats {
        margin-top: 8px;
    }

    .usage-badge {
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .usage-badge i {
        font-size: 12px;
    }

    .usage-badge strong {
        font-weight: 700;
        margin: 0 2px;
    }

    .feature-limit-action {
        flex-shrink: 0;
    }

    .btn-upgrade {
        background: #fff;
        color: #ff9800;
        border: none;
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        text-decoration: none;
    }

    .btn-upgrade:hover {
        background: #f5f5f5;
        color: #f57c00;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
        text-decoration: none;
    }

    .btn-upgrade:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .btn-upgrade i {
        font-size: 12px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .feature-limit-banner {
            padding: 15px 0;
        }

        .feature-limit-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .feature-limit-icon-wrapper {
            width: 45px;
            height: 45px;
        }

        .feature-limit-icon-wrapper i {
            font-size: 20px;
        }

        .feature-limit-title {
            font-size: 16px;
        }

        .feature-limit-message {
            font-size: 13px;
        }

        .btn-upgrade {
            width: 100%;
            justify-content: center;
            padding: 14px 24px;
        }

        .feature-limit-action {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .feature-limit-banner {
            padding: 12px 0;
        }

        .feature-limit-title {
            font-size: 15px;
        }

        .feature-limit-message {
            font-size: 12px;
        }
    }

    /* Overlay Styles */
    .feature-limit-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.2);
        z-index: 1040;
        pointer-events: all;
    }

    /* Ensure sidebar is above overlay */
    .main-sidebar {
        z-index: 1052 !important;
        position: relative;
    }

    /* Block interactions in content area when overlay is present */
    body:has(.feature-limit-overlay) .content-wrapper {
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    /* Ensure sidebar interactions are allowed */
    body:has(.feature-limit-overlay) .main-sidebar,
    body:has(.feature-limit-overlay) .sidebar,
    body:has(.feature-limit-overlay) aside {
        pointer-events: all !important;
        user-select: auto !important;
        -webkit-user-select: auto !important;
        -moz-user-select: auto !important;
        -ms-user-select: auto !important;
    }
    
    /* Ensure header interactions are allowed */
    body:has(.feature-limit-overlay) .main-header,
    body:has(.feature-limit-overlay) .navbar {
        pointer-events: all !important;
    }
    
    /* Ensure alert container interactions are allowed */
    body:has(.feature-limit-overlay) .feature-limit-alert-container {
        pointer-events: all !important;
        user-select: auto !important;
        -webkit-user-select: auto !important;
        -moz-user-select: auto !important;
        -ms-user-select: auto !important;
    }
    
    /* Fallback for browsers that don't support :has() */
    .content-wrapper.blocked {
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    /* Prevent scrolling when limit is reached */
    body:has(.feature-limit-overlay) {
        overflow: hidden;
    }

    /* Make sure alert is positioned correctly */
    .feature-limit-alert-container {
        position: sticky;
        top: 0;
        width: 100%;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.querySelector('.feature-limit-overlay');
        const contentWrapper = document.querySelector('.content-wrapper');

        if (overlay) {
            // Add blocked class for browsers that don't support :has()
            if (contentWrapper) {
                contentWrapper.classList.add('blocked');
            }
            
            // Prevent body scrolling
            document.body.style.overflow = 'hidden';

            overlay.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });

            // Prevent form submissions in content area
            document.querySelectorAll('.content-wrapper form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            });

            // Prevent link clicks in content area only (except upgrade button)
            document.querySelectorAll('.content-wrapper a:not(.btn-upgrade)').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!this.classList.contains('btn-upgrade')) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                });
            });

            // Prevent button clicks in content area only (except upgrade button)
            document.querySelectorAll(
                    '.content-wrapper button:not(.btn-upgrade), .content-wrapper .btn:not(.btn-upgrade)')
                .forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        if (!this.classList.contains('btn-upgrade')) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                    });
                });

            // Prevent input interactions in content area only
            document.querySelectorAll(
                '.content-wrapper input, .content-wrapper textarea, .content-wrapper select').forEach(
                input => {
                    input.addEventListener('focus', function(e) {
                        e.preventDefault();
                    });
                    input.setAttribute('disabled', 'disabled');
                });

            // Allow sidebar interactions
            document.querySelectorAll('.main-sidebar a, .sidebar a, aside a').forEach(link => {
                link.addEventListener('click', function(e) {
                    return true;
                });
            });
        }
    });
</script>
