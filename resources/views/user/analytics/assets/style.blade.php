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
        position: sticky;
        top: 1rem;
        align-self: flex-start;
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

    .analytics-page-avatar-all {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(24, 119, 242, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
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

    .analytics-insight-tabs {
        border-bottom: 1px solid #dee2e6;
    }

    .analytics-insight-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.5rem 1rem;
        font-weight: 500;
    }

    .analytics-insight-tabs .nav-link:hover {
        color: #1877F2;
        border-color: transparent;
    }

    .analytics-insight-tabs .nav-link.active {
        color: #1877F2;
        background: transparent;
        border-bottom-color: #1877F2;
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

    .analytics-posts-tab-content {
        padding: 1.25rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .analytics-post-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
        overflow: hidden;
    }

    .analytics-post-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: #dee2e6;
    }

    .analytics-post-card-inner {
        display: flex;
        gap: 1.25rem;
        align-items: flex-start;
    }

    .analytics-post-thumb-wrap {
        width: 140px;
        flex-shrink: 0;
    }

    .analytics-post-thumb {
        width: 140px;
        height: 140px;
        object-fit: cover;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .analytics-post-thumb-placeholder {
        width: 140px;
        height: 140px;
        border-radius: 8px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #adb5bd;
    }

    .analytics-post-content {
        flex: 1;
        min-width: 0;
    }

    .analytics-post-date {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .analytics-post-message {
        font-size: 0.95rem;
        line-height: 1.5;
        color: #333;
        /* white-space: pre-wrap; */
        word-break: break-word;
    }

    .analytics-post-insights-wrap {
        margin-top: 0.5rem;
    }

    .analytics-post-insights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 0.75rem;
    }

    .analytics-post-insight-item {
        display: flex;
        flex-direction: column;
        padding: 0.5rem 0.75rem;
        background: rgba(24, 119, 242, 0.06);
        border-radius: 8px;
        border: 1px solid rgba(24, 119, 242, 0.12);
    }

    .analytics-post-insight-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1877F2;
        line-height: 1.2;
    }

    .analytics-post-insight-label {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-top: 0.2rem;
    }

    .analytics-post-metrics span {
        font-size: 0.9rem;
    }

    .analytics-posts-placeholder {
        min-height: 200px;
    }

    @media (max-width: 576px) {
        .analytics-post-card-inner {
            flex-direction: column;
        }

        .analytics-post-thumb-wrap,
        .analytics-post-thumb,
        .analytics-post-thumb-placeholder {
            width: 100%;
            height: 180px;
        }
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

    /* Chart metric selector - modern dropdown */
    .chart-metric-dropdown-wrap {
        display: inline-block;
        margin-left: 0.25rem;
    }

    .chart-metric-trigger {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1877F2;
        background: rgba(24, 119, 242, 0.08);
        border: 1px solid rgba(24, 119, 242, 0.25);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .chart-metric-trigger:hover {
        background: rgba(24, 119, 242, 0.14);
        border-color: rgba(24, 119, 242, 0.4);
        box-shadow: 0 2px 6px rgba(24, 119, 242, 0.15);
    }

    .chart-metric-trigger:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.2);
    }

    .chart-metric-trigger::after {
        display: none;
    }

    .chart-metric-trigger-chevron {
        font-size: 0.65rem;
        opacity: 0.8;
        transition: transform 0.2s ease;
    }

    .chart-metric-dropdown-wrap.show .chart-metric-trigger-chevron {
        transform: rotate(180deg);
    }

    .chart-metric-dropdown {
        min-width: 180px;
        padding: 0.5rem;
        margin-top: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12), 0 2px 10px rgba(0, 0, 0, 0.06);
        background: #fff;
    }

    .chart-metric-option {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #495057;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .chart-metric-option:hover {
        background: rgba(24, 119, 242, 0.08);
        color: #1877F2;
    }

    .chart-metric-option.active {
        background: rgba(24, 119, 242, 0.12);
        color: #1877F2;
        font-weight: 600;
    }

    .chart-metric-option-circle {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid #adb5bd;
        border-radius: 50%;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }

    .chart-metric-option:hover .chart-metric-option-circle {
        border-color: #1877F2;
    }

    .chart-metric-option-circle.selected {
        border-color: #1877F2;
        background: #1877F2;
    }

    .chart-metric-option span:last-child {
        flex: 1;
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
