<style>
    /* Analytics layout: sub-sidebar + main content */
    .analytics-layout {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .analytics-sidebar {
        width: 280px;
        flex-shrink: 0;
    }

    .analytics-sidebar-inner {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        height: 420px;
        display: flex;
        flex-direction: column;
    }

    .analytics-sidebar-search {
        padding: 10px 12px;
        border-bottom: 1px solid #e9ecef;
        flex-shrink: 0;
    }

    .analytics-sidebar-search .input-group-text {
        border-left: 0;
    }

    .analytics-sidebar-search .form-control {
        border-right: 0;
    }

    .analytics-sidebar-search .form-control:focus {
        box-shadow: none;
    }

    .analytics-sidebar-search .input-group:focus-within .input-group-text {
        border-color: #80bdff;
    }

    .analytics-sidebar-cards {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
    }

    .analytics-sidebar-cards::-webkit-scrollbar {
        width: 6px;
    }

    .analytics-sidebar-cards::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .analytics-sidebar-cards::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .analytics-main {
        flex: 1;
        min-width: 0;
    }

    .analytics-main-full {
        width: 100%;
    }

    .analytics-page-card {
        text-decoration: none;
        color: inherit;
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        display: flex;
        margin-bottom: 8px;
    }

    .analytics-page-card:last-child {
        margin-bottom: 0;
    }

    .analytics-page-card:hover {
        border-color: #dee2e6;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .analytics-page-card.active {
        border-color: #28a745;
        background: linear-gradient(135deg, #f0fff4 0%, #fff 100%);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
    }

    .analytics-page-card.active::before {
        content: '\f00c';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: 6px;
        right: 8px;
        background: #28a745;
        color: #fff;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        font-size: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    .analytics-page-card-inner {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-width: 0;
    }

    .analytics-page-avatar {
        position: relative;
        flex-shrink: 0;
    }

    .analytics-page-avatar img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e9ecef;
    }

    .analytics-page-avatar .platform-badge.facebook {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #1877F2;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: #fff;
        border: 2px solid #fff;
    }

    .analytics-page-details {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1;
    }

    .analytics-page-name {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .analytics-page-username {
        font-size: 11px;
        color: #888;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .analytics-page-insights {
        padding: 1rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .page-insight-card {
        display: flex;
        flex-direction: column;
        padding: 0.75rem;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        text-align: center;
        transition: box-shadow 0.2s ease;
    }

    .page-insight-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .page-insight-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1877F2;
        line-height: 1.2;
    }

    .page-insight-label {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 0.25rem;
    }

    .analytics-post-card {
        border-left: 4px solid #1877F2;
        transition: box-shadow 0.2s ease;
    }

    .analytics-post-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }

    .analytics-post-thumbnail {
        max-height: 120px;
        overflow: hidden;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .analytics-post-thumbnail img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }

    .analytics-metrics {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .metric-box {
        display: flex;
        flex-direction: column;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }

    .metric-value {
        font-size: 1.25rem;
        font-weight: 600;
        color: #333;
    }

    .metric-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .empty-state-icon i {
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .analytics-layout {
            flex-direction: column;
        }

        .analytics-sidebar {
            width: 100%;
        }

        .analytics-sidebar-inner {
            height: 280px;
        }
    }
</style>
