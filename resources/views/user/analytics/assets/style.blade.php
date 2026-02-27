<style>
    /* Analytics layout: sub-sidebar + main content */
    .analytics-layout {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .analytics-sidebar {
        width: 250px;
        flex-shrink: 0;
    }

    .analytics-sidebar-inner {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        height: 520px;
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

    /* Insight comparison badges (increase/decrease) */
    .insight-comparison {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 6px;
        border-radius: 4px;
        white-space: nowrap;
        cursor: help;
        flex-shrink: 0;
    }

    .insight-comparison.has-tooltip {
        position: relative;
        overflow: visible;
    }

    .insight-comparison.has-tooltip::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%) translateY(-5px);
        padding: 6px 10px;
        background: #333;
        color: #fff;
        font-size: 11px;
        font-weight: 500;
        white-space: nowrap;
        border-radius: 6px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s ease;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        visibility: hidden;
        min-width: max-content;
    }

    .insight-comparison.has-tooltip::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 2px);
        left: 50%;
        transform: translateX(-50%);
        border-width: 5px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        z-index: 10001;
        visibility: hidden;
    }

    .insight-comparison.has-tooltip:hover::after,
    .insight-comparison.has-tooltip:hover::before {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .insight-comparison-up {
        color: #28a745;
        background: rgba(40, 167, 69, 0.12);
    }

    .insight-comparison-down {
        color: #dc3545;
        background: rgba(220, 53, 69, 0.12);
    }

    .insight-comparison-neutral {
        color: #6c757d;
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
        padding: 1.25rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .analytics-insight-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
    }

    @media (min-width: 576px) {
        .analytics-insight-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 992px) {
        .analytics-insight-cards {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .page-insight-card {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
        min-height: 90px;
    }

    .page-insight-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-color: #dee2e6;
    }

    .page-insight-card .d-flex {
        flex-wrap: nowrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        min-height: 1.5em;
        flex: 1;
    }

    .page-insight-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1877F2;
        line-height: 1.2;
        overflow-wrap: break-word;
    }

    .page-insight-label {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 0.35rem;
        line-height: 1.3;
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
