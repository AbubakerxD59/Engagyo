<style>
    /* --- Full-height layout: no body scroll, tabs scroll internally --- */
    .content-wrapper {
        overflow: hidden !important;
    }

    .page-content {
        height: calc(100vh - 110px);
        overflow: hidden;
    }

    .page-content>.feature-limit-alert-container {
        flex-shrink: 0;
    }

    .page-content>.content-header {
        flex-shrink: 0;
        padding: 0;
        margin: 0;
    }

    .page-content>.content {
        flex: 1;
        height: calc(100vh - 110px);
        overflow: hidden;
        padding: 0 0.5rem;
    }

    .page-content>.content>.container-fluid {
        height: 100%;
        padding: 0;
    }

    .schedule-page-wrapper {
        display: flex;
        gap: 0;
        height: 100%;
        min-height: 0;
    }

    .accounts-sidebar {
        flex-shrink: 0;
        width: 260px;
        transition: width 0.25s ease;
        border-right: 1px solid #e9ecef;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 10px 0 0 10px;
        padding: 10px 8px;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        position: relative;
        z-index: 100;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .accounts-sidebar::-webkit-scrollbar {
        display: none;
    }

    .accounts-sidebar.collapsed {
        width: 4rem;
        padding: 10px 6px;
    }

    /* --- Sticky search area (stays on top when scrolling) --- */
    .accounts-sidebar-sticky {
        position: sticky;
        top: 0;
        z-index: 10;
        flex-shrink: 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        padding-bottom: 8px;
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
    }

    /* --- Toggle button (expanded state only: collapse sidebar, above search bar) --- */
    .accounts-sidebar-toggle {
        display: none;
        width: 28px;
        height: 28px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        transition: color 0.2s, background 0.2s;
        margin-bottom: 8px;
        margin-left: auto;
    }

    .accounts-sidebar-toggle:hover {
        background: #e9ecef;
        color: #495057;
    }

    .accounts-sidebar:not(.collapsed) .accounts-sidebar-toggle {
        display: flex;
    }

    /* --- Sidebar search icon (collapsed state) --- */
    .accounts-sidebar-search-icon {
        display: none;
        width: 40px;
        height: 40px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: #fff;
        color: #6c757d;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: background 0.2s, color 0.2s;
        margin: 0 auto 6px auto;
        padding: 0;
    }

    .accounts-sidebar-search-icon:hover {
        background: #e9ecef;
        color: #495057;
    }

    .accounts-sidebar.collapsed .accounts-sidebar-search-icon {
        display: flex;
    }

    /* --- Sidebar search bar (expanded state) --- */
    .accounts-sidebar-search-wrap {
        display: none;
        padding: 0 4px;
        margin-top: 0;
    }

    .accounts-sidebar:not(.collapsed) .accounts-sidebar-search-wrap {
        display: block;
    }

    .accounts-sidebar-search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 6px 10px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .accounts-sidebar-search-box:focus-within {
        border-color: #86b7fe;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
    }

    .accounts-sidebar-search-icon-inner {
        color: #adb5bd;
        font-size: 13px;
        flex-shrink: 0;
    }

    .accounts-sidebar-search-input {
        border: none;
        outline: none;
        background: transparent;
        font-size: 13px;
        color: #1a1a1a;
        width: 100%;
        padding: 0;
    }

    .accounts-sidebar-search-input::placeholder {
        color: #adb5bd;
    }

    .accounts-sidebar-search-clear {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border: none;
        background: #e9ecef;
        border-radius: 50%;
        color: #6c757d;
        font-size: 10px;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
        transition: background 0.2s;
    }

    .accounts-sidebar-search-clear:hover {
        background: #dee2e6;
        color: #495057;
    }

    .schedule-main-content {
        flex: 1;
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .schedule-main-content>.card {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
        border-radius: 0;
        border: none;
        box-shadow: none;
    }

    .schedule-main-content>.card>.card-body {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 0;
    }

    .selected-account-container {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .selected-account-sticky-wrap {
        flex-shrink: 0;
        z-index: 99;
        background: #fff;
        padding: 12px 16px;
        border-bottom: 1px solid #e9ecef;
        box-shadow: 0 1px 0 0 rgba(0, 0, 0, 0.03);
    }

    /* --- Accounts Container inside sidebar --- */
    .accounts-sidebar .accounts-container {
        overflow: visible;
        padding: 0 4px 10px 4px;
        margin-bottom: 0;
        border: none;
        border-radius: 0;
        background: transparent;
    }

    .accounts-sidebar.collapsed .accounts-container {
        padding: 8px 0 16px 0;
    }

    .accounts-container::-webkit-scrollbar {
        width: 6px;
    }

    .accounts-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .accounts-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .accounts-container::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }

    .accounts-sidebar .accounts-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        width: 100%;
        box-sizing: border-box;
    }

    .accounts-sidebar:not(.collapsed) .accounts-grid {
        grid-template-columns: 1fr;
    }

    .accounts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
        width: 100%;
        box-sizing: border-box;
    }

    .account-card {
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 10px 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        overflow: visible;
    }

    .account-card:hover {
        border-color: #dee2e6;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .account-card.active {
        border-color: #28a745;
        background: linear-gradient(135deg, #f0fff4 0%, #fff 100%);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
    }

    .account-card.active::before {
        content: '\f00c';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: -6px;
        right: -6px;
        background: #28a745;
        color: #fff;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
    }

    .account-card-inner {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Sidebar collapsed: show only profile pic + platform icon */
    .accounts-sidebar.collapsed .account-details {
        display: none !important;
    }

    .accounts-sidebar.collapsed .account-card-inner {
        justify-content: center;
        gap: 0;
    }

    .accounts-sidebar.collapsed .account-card {
        padding: 6px;
        min-height: auto;
    }

    .accounts-sidebar.collapsed .account-avatar img {
        width: 36px;
        height: 36px;
    }

    .accounts-sidebar.collapsed .platform-badge {
        width: 14px;
        height: 14px;
        font-size: 7px;
        bottom: -1px;
        right: -1px;
    }

    /* Hide tick icon on selected accounts in sidebar */
    .accounts-sidebar .account-card.active::before,
    .accounts-sidebar .account-card.active.has-tooltip::before {
        display: none !important;
        content: none !important;
    }

    .account-avatar {
        position: relative;
        flex-shrink: 0;
    }

    .account-avatar img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e9ecef;
    }

    .platform-badge {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #fff;
        border: 2px solid #fff;
    }

    .platform-badge.facebook {
        background: #1877F2;
    }

    .platform-badge.pinterest {
        background: #E60023;
    }

    .platform-badge.tiktok {
        background: #000000;
    }

    .account-details {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1;
    }

    .account-details .account-name {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .account-details .account-username {
        font-size: 11px;
        color: #888;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Tooltip Styling for Account Cards */
    .account-card.has-tooltip {
        position: relative;
        overflow: visible;
    }

    .account-card.has-tooltip::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 10px);
        left: 50%;
        transform: translateX(-50%) translateY(-5px);
        padding: 8px 12px;
        background: #333;
        color: #fff;
        font-size: 12px;
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

    .account-card.has-tooltip::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 4px);
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        z-index: 10001;
        visibility: hidden;
    }

    /* For active cards, hide the tooltip arrow (::before) and only show checkmark */
    .account-card.active.has-tooltip::before {
        content: '\f00c';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: -6px;
        right: -6px;
        bottom: auto;
        left: auto;
        transform: none;
        background: #28a745;
        color: #fff;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-width: 0;
        opacity: 1;
        visibility: visible;
        z-index: 100;
        pointer-events: auto;
    }

    .account-card.has-tooltip:hover::after,
    .account-card.has-tooltip:hover::before {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    /* Ensure accounts container doesn't clip tooltips */
    .accounts-container {
        overflow: visible;
    }

    .accounts-grid {
        position: relative;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .accounts-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }

    @media (max-width: 578px) {
        .queue-timeslots-post-col {
            flex: 0 0 100%;
            width: 100%;
        }
    }

    @media(max-width: 425px) {
        .selected-account-action-btn span {
            display: none;
        }
    }

    /* --- All Channels icon (sidebar + header) --- */
    .all-channels-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1.5px solid #e5e7eb;
        background: #fff;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 5px;
        padding: 8px;
        flex-shrink: 0;
    }

    .all-channels-icon span {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #9ca3af;
    }

    .accounts-sidebar.collapsed .all-channels-icon {
        width: 36px;
        height: 36px;
        padding: 8px;
        gap: 2px;
    }

    .selected-account-allch-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: 1.5px solid #e5e7eb;
        background: #fff;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1px;
        padding: 11px;
        flex-shrink: 0;
    }

    .selected-account-allch-icon span {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #6b7280;
    }

    /* --- Selected account header (above content textarea) --- */
    .selected-account-header {
        /* background: #fff; */
        /* border-radius: 10px; */
        padding: 0px 16px;
        margin-bottom: 5px;
        /* border: 1px solid #e0e0e0; */
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .selected-account-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .selected-account-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .selected-account-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e0e0e0;
        display: block;
    }

    .selected-account-platform-badge {
        position: absolute;
        bottom: -2px;
        left: -2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #fff;
        border: 2px solid #fff;
    }

    .selected-account-platform-badge.facebook {
        background: #1877F2;
    }

    .selected-account-platform-badge.pinterest {
        background: #E60023;
    }

    .selected-account-platform-badge.tiktok {
        background: #000000;
    }

    .selected-account-text {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .selected-account-header-settings-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: none;
        border-radius: 10px;
        background: transparent;
        color: #333;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .selected-account-header-settings-btn:hover {
        background: #e8e8e8;
        border-radius: 10px;
        color: #000;
    }

    .selected-account-header-settings-btn i {
        font-size: 14px;
    }

    .selected-account-header-refresh-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: none;
        border-radius: 10px;
        background: transparent;
        color: #333;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .selected-account-header-refresh-btn:hover:not(:disabled) {
        background: #e8e8e8;
        border-radius: 10px;
        color: #000;
    }

    .selected-account-header-refresh-btn i {
        font-size: 14px;
    }

    .selected-account-header-refresh-btn.is-syncing {
        color: #0d6efd;
        background: #e7f1ff;
        cursor: not-allowed;
        pointer-events: none;
    }

    .selected-account-header-refresh-btn.is-syncing i {
        animation: selected-account-refresh-spin 0.8s linear infinite;
    }

    @keyframes selected-account-refresh-spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .selected-account-header-sync-msg {
        font-size: 13px;
        color: #0d6efd;
        white-space: nowrap;
    }

    .selected-account-name {
        font-size: 15px;
        font-weight: 600;
        color: #000;
        line-height: 1.3;
    }

    .selected-account-name-wrap {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .selected-account-tagline {
        font-size: 13px;
        color: #333;
        line-height: 1.3;
    }

    /* Header right-side actions (List, Calendar, New Post, Tags, Location, More) */
    .selected-account-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .selected-account-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        background: #f1f3f5;
        color: #1a1a1a;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s, color 0.2s;
    }

    .selected-account-action-btn:hover {
        background: #e9ecef;
        border-color: #ced4da;
    }

    .selected-account-action-btn i {
        font-size: 12px;
        opacity: 0.9;
    }

    .selected-account-action-btn.selected-account-view-list.is-active {
        background: #2d8a4e;
        border-color: #2d8a4e;
        color: #fff;
    }

    .selected-account-action-btn.selected-account-view-list.is-active:hover {
        background: #26803f;
        border-color: #26803f;
        color: #fff;
    }

    .selected-account-action-btn.selected-account-new-post {
        background: #495057;
        border-color: #495057;
        color: #fff;
    }

    .selected-account-action-btn.selected-account-new-post:hover {
        background: #343a40;
        border-color: #343a40;
        color: #fff;
    }

    .selected-account-action-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        background: #f8f9fa;
        color: #1a1a1a;
        font-size: 13px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }

    .selected-account-action-chip:hover {
        background: #e9ecef;
        border-color: #ced4da;
    }

    .selected-account-action-chip i:first-child {
        font-size: 11px;
        opacity: 0.85;
    }

    .selected-account-timezone-wrap .selected-account-timezone-text {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* --- Posts status tabs (Queue, Sent) - light theme --- */
    .posts-status-tabs {
        display: flex;
        align-items: center;
        gap: 0;
        /* background: #f8f9fa; */
        /* border-radius: 8px; */
        padding: 4px;
        /* margin-bottom: 14px; */
        /* border: 1px solid #dee2e6; */
    }

    .posts-status-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border: none;
        background: transparent;
        color: #1a1a1a;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border-radius: 6px;
        position: relative;
        transition: color 0.2s, background 0.2s;
    }

    .posts-status-tab:hover {
        background: rgba(0, 0, 0, 0.04);
        color: #1a1a1a;
    }

    .posts-status-tab.is-active {
        color: #1a1a1a;
    }

    .posts-status-tab.is-active::after {
        content: '';
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: 6px;
        height: 2px;
        background: #0b4423;
        border-radius: 2px;
    }

    .posts-status-tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        background: #e9ecef;
        color: #495057;
        font-size: 12px;
        font-weight: 600;
        border-radius: 50%;
    }

    .posts-status-tab.is-active .posts-status-tab-badge {
        background: #dee2e6;
        color: #212529;
    }

    /* --- Posts search bar (Queue & Sent tabs): icon only by default, expands on click --- */
    /* Matches design: rounded border (#DEE2E6), white bg, slate icon (#5A6268), gray placeholder */
    .posts-search-wrap {
        flex: 0 0 auto;
        margin-left: 16px;
        width: 40px;
        overflow: hidden;
        border: 1px solid #DEE2E6;
        border-radius: 10px;
        background: #fff;
        transition: width 0.25s ease, border-color 0.2s;
        cursor: pointer;
    }

    .posts-search-wrap.is-expanded {
        width: 280px;
        max-width: 280px;
        cursor: default;
    }

    .posts-search-wrap:focus-within {
        border-color: #0b4423;
    }

    .posts-status-tabs .posts-search-wrap {
        display: flex !important;
    }

    .posts-search-inner {
        position: relative;
        display: flex;
        align-items: center;
        width: 280px;
        min-width: 280px;
    }

    .posts-search-icon {
        flex-shrink: 0;
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #5A6268;
        font-size: 14px;
        cursor: pointer;
    }

    .posts-search-input {
        flex: 1;
        min-width: 0;
        padding: 6px 36px 6px 8px;
        border: none;
        border-radius: 0;
        font-size: 14px;
        color: #1a1a1a;
        background: transparent;
        outline: none;
    }

    .posts-search-input::placeholder {
        color: #ADB5BD;
    }

    .posts-search-clear {
        position: absolute;
        right: 8px;
        padding: 6px;
        border: none;
        background: transparent;
        color: #5A6268;
        cursor: pointer;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .posts-search-clear:hover {
        color: #1a1a1a;
        background: rgba(0, 0, 0, 0.06);
    }

    /* --- Queue tab: timeslots section (light theme) --- */
    .queue-timeslots-section {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 20px 10%;
    }

    .queue-timeslots-section::-webkit-scrollbar {
        width: 5px;
    }

    .queue-timeslots-section::-webkit-scrollbar-track {
        background: transparent;
    }

    .queue-timeslots-section::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 10px;
    }

    .posts-grid-section {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 20px 10%;
    }

    .posts-grid-section::-webkit-scrollbar {
        width: 5px;
    }

    .posts-grid-section::-webkit-scrollbar-track {
        background: transparent;
    }

    .posts-grid-section::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 10px;
    }

    .queue-timeslots-content {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .queue-timeslots-day-group {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .queue-timeslots-day-header {
        font-size: 14px;
        font-weight: 500;
        text-transform: capitalize;
        color: #1a1a1a;
        margin: 0 0 4px 0;
    }

    .queue-timeslots-row {
        display: flex;
        align-items: flex-start;
        gap: 24px;
        margin-bottom: 16px;
    }

    .queue-timeslots-row:last-child {
        margin-bottom: 0;
    }

    .queue-timeslots-time-col {
        flex-shrink: 0;
        width: 80px;
        padding-top: 4px;
    }

    .queue-timeslots-time {
        font-size: 13px;
        color: #495057;
        width: 10%;
        font-variant-numeric: tabular-nums;
    }

    .queue-timeslots-type {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    .queue-timeslots-type i {
        font-size: 10px;
        opacity: 0.8;
    }

    .queue-timeslots-post-col {
        flex: 1;
        min-width: 0;
    }

    .queue-timeslots-posts-block {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .queue-timeslots-posts-block .queue-post-card {
        margin-bottom: 0;
    }

    .queue-timeslots-new-btn {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        min-width: 80px;
        width: 100%;
        padding: 8px 14px;
        background: #e9ecef;
        color: #495057;
        font-size: 15px;
        font-weight: 700;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s, color 0.2s, border-color 0.2s;
    }

    .queue-timeslots-new-btn:hover {
        background: #dee2e6;
        color: #212529;
        border-color: #ced4da;
    }

    /* --- Queue post card (queued post in timeslot) --- */
    .queue-post-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        overflow: hidden;
    }

    .queue-post-card-inner {
        padding: 16px;
    }

    .queue-post-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .queue-post-account {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .queue-post-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .queue-post-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        display: block;
    }

    .queue-post-platform-badge {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #fff;
        border: 2px solid #fff;
    }

    .queue-post-platform-badge.facebook {
        background: #1877f2;
    }

    .queue-post-platform-badge.pinterest {
        background: #e60023;
    }

    .queue-post-platform-badge.tiktok {
        background: #000;
    }

    .queue-post-format-badge {
        position: static;
        bottom: auto;
        left: auto;
        right: auto;
        top: auto;
        padding: 2px 6px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        color: #fff;
        border: 2px solid #fff;
        white-space: nowrap;
        line-height: 1;
    }

    .queue-timeslots-format-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 4px;
    }

    .sent-post-format-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 4px;
    }

    .queue-post-format-badge.reel {
        background: #e11d48;
    }

    .queue-post-format-badge.story {
        background: #f59e0b;
    }

    .queue-post-account-name {
        font-weight: 600;
        font-size: 14px;
        color: #1a1a1a;
    }

    .queue-post-chat-btn {
        width: 32px;
        height: 32px;
        border: none;
        background: #f1f3f4;
        border-radius: 6px;
        color: #6c757d;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s, color 0.2s;
    }

    .queue-post-chat-btn:hover {
        background: #e9ecef;
        color: #495057;
    }

    .queue-post-card-body {
        display: flex;
        gap: 16px;
        margin-bottom: 12px;
        min-height: 60px;
    }

    .queue-post-text-wrap {
        flex: 1;
        min-width: 0;
    }

    .queue-post-text {
        font-size: 14px;
        color: #495057;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-word;
        text-transform: capitalize;
    }

    .queue-post-see-more-btn {
        margin-top: 6px;
        padding: 0;
        background: none;
        border: none;
        font-size: 13px;
        font-weight: 600;
        color: #0d6efd;
        cursor: pointer;
        transition: color 0.2s;
    }

    .queue-post-see-more-btn:hover {
        color: #0a58ca;
        text-decoration: underline;
    }

    .queue-link-title+.queue-post-see-more-btn,
    .queue-link-desc+.queue-post-see-more-btn {
        display: block;
        margin-top: 4px;
    }

    .queue-post-image-wrap {
        flex-shrink: 0;
        margin-left: auto;
        width: 120px;
        height: 120px;
        border-radius: 6px;
        overflow: hidden;
        background: #f8f9fa;
    }

    .queue-post-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* --- Link post preview --- */
    .queue-post-card-link .queue-post-card-body {
        padding: 0;
        margin-bottom: 12px;
        min-height: 0;
    }

    .queue-link-preview {
        display: flex;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        overflow: hidden;
    }

    .queue-link-preview-no-thumb .queue-link-content {
        padding: 14px 16px;
    }

    .queue-link-thumbnail {
        flex-shrink: 0;
        width: 120px;
        min-height: 100px;
        background: #e9ecef;
    }

    .queue-link-thumbnail img {
        width: 100%;
        height: 100%;
        min-height: 100px;
        object-fit: cover;
        display: block;
    }

    .queue-link-content {
        flex: 1;
        padding: 12px 14px;
        min-width: 0;
    }

    .queue-link-title {
        font-weight: 700;
        font-size: 14px;
        color: #1a1a1a;
        line-height: 1.4;
        margin-bottom: 4px;
    }

    .queue-link-url {
        font-size: 12px;
        color: #6c757d;
        word-break: break-all;
        margin-bottom: 6px;
    }

    .queue-link-url a {
        color: #6c757d;
        text-decoration: none;
    }

    .queue-link-url a:hover {
        color: #1877f2;
        text-decoration: underline;
    }

    .queue-link-desc {
        font-size: 13px;
        color: #495057;
        line-height: 1.45;
    }

    .queue-post-card-footer {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-top: 12px;
        border-top: 1px solid #f1f3f4;
    }

    .queue-post-created {
        flex: 1;
        font-size: 12px;
        color: #adb5bd;
    }

    .queue-post-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
    }

    .queue-post-publish-now-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 600;
        color: #fff;
        background: #1877f2;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s, opacity 0.2s;
    }

    .queue-post-publish-now-btn:hover {
        background: #166fe5;
    }

    .queue-post-edit-btn,
    .queue-post-more-btn {
        width: 30px;
        height: 30px;
        border: none;
        background: #f1f3f4;
        border-radius: 50%;
        color: #6c757d;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s, color 0.2s;
    }

    .queue-post-edit-btn i,
    .queue-post-more-btn i {
        font-size: 11px;
    }

    .queue-post-edit-btn:hover,
    .queue-post-more-btn:hover {
        background: #e9ecef;
        color: #495057;
    }

    .queue-post-more-menu {
        position: absolute;
        right: 0;
        bottom: 100%;
        margin-bottom: 4px;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 4px 0;
        z-index: 100;
        min-width: 120px;
    }

    .queue-post-more-btn {
        position: relative;
    }

    .queue-post-delete-btn {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 8px 14px;
        font-size: 13px;
        color: #dc3545;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
    }

    .queue-post-delete-btn:hover {
        background: #fff5f5;
    }

    .queue-timeslots-empty {
        padding: 20px;
        text-align: center;
    }

    .queue-timeslots-empty-text {
        margin: 0;
        font-size: 14px;
        color: #6c757d;
    }

    .empty-state-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        text-align: center;
        color: #9ca3af;
    }

    .empty-state-box i {
        font-size: 40px;
        margin-bottom: 12px;
        color: #d1d5db;
    }

    .empty-state-box p {
        font-size: 15px;
        font-weight: 500;
        color: #6b7280;
        margin: 0;
    }

    /* --- Sent posts timeline layout --- */
    .sent-posts-timeline {
        display: flex;
        flex-direction: column;
        gap: 32px;
    }

    .sent-day-group {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .sent-day-header {
        font-size: 14px;
        font-weight: 500;
        text-transform: capitalize;
        color: #111827;
        margin: 0 0 4px 0;
        padding: 0;
    }

    .sent-day-header span {
        font-weight: 400;
        color: #6b7280;
    }

    .sent-post-row {
        display: flex;
        gap: 24px;
        align-items: flex-start;
        padding: 16px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .sent-post-row:last-child {
        border-bottom: none;
    }

    .sent-post-time-col {
        flex-shrink: 0;
        width: 70px;
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding-top: 4px;
    }

    .sent-post-time {
        font-size: 13px;
        font-weight: 700;
        color: #111827;
        font-variant-numeric: tabular-nums;
        line-height: 1.3;
    }

    .sent-post-source {
        font-size: 11.5px;
        color: #9ca3af;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .sent-post-source i {
        font-size: 10px;
    }

    .sent-post-card-col {
        flex: 1;
        min-width: 0;
    }

    /* --- New sent card design --- */
    .sent-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }

    .sent-card:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .sent-card-body {
        display: flex;
        gap: 16px;
        padding: 16px;
    }

    .sent-card-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .sent-card-account {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sent-card-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .sent-card-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e5e7eb;
    }

    .sent-card-platform-badge {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 9px;
        border: 2px solid #fff;
    }

    .sent-card-platform-badge.facebook {
        background: #1877F2;
    }

    .sent-card-platform-badge.pinterest {
        background: #E60023;
    }

    .sent-card-platform-badge.tiktok {
        background: #000;
    }

    .sent-card-account-name {
        font-size: 14px;
        font-weight: 600;
        color: #111827;
    }

    .sent-card-title {
        font-size: 13.5px;
        color: #4b5563;
        line-height: 1.5;
        margin: 0;
        word-break: break-word;
        text-transform: capitalize;
    }

    .sent-card-image {
        flex-shrink: 0;
        width: 130px;
        height: 110px;
        border-radius: 8px;
        overflow: hidden;
        background: #f3f4f6;
    }

    .sent-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sent-card-video-thumb {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 28px;
    }

    /* Stats row */
    .sent-card-stats {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 10px 16px;
        border-top: 1px solid #f3f4f6;
        background: #fafbfc;
    }

    .sent-card-stat {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        font-size: 12px;
        color: #6b7280;
        text-align: center;
        padding: 0 4px;
    }

    .sent-card-stat i {
        font-size: 14px;
        color: #9ca3af;
        margin-bottom: 1px;
    }

    .sent-card-stat .stat-label {
        font-size: 11px;
        color: #9ca3af;
        font-weight: 500;
    }

    .sent-card-stat strong {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
    }

    /* Footer */
    .sent-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-top: 1px solid #f3f4f6;
        flex-wrap: wrap;
        gap: 8px;
    }

    .sent-card-published-via {
        font-size: 13px;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .sent-card-published-via-tooltip {
        cursor: help;
        text-decoration: underline;
        text-decoration-style: dotted;
        text-underline-offset: 2px;
    }

    .sent-card-platform-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        color: #fff;
        font-size: 10px;
    }

    .sent-card-platform-icon.facebook {
        background: #1877F2;
    }

    .sent-card-platform-icon.pinterest {
        background: #E60023;
    }

    .sent-card-platform-icon.tiktok {
        background: #000;
    }

    .sent-card-footer-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sent-card-view-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        color: #374151;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.15s;
    }

    .sent-card-view-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
        color: #111827;
        text-decoration: none;
    }

    .sent-card-delete-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border: 1px solid #fecaca;
        border-radius: 8px;
        background: #fff;
        color: #dc2626;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
    }

    .sent-card-delete-btn:hover:not(:disabled) {
        background: #fef2f2;
        border-color: #f87171;
        color: #b91c1c;
    }

    .sent-card-delete-btn:disabled,
    .sent-card-delete-btn.is-deleting {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* 3-dot menu (shared) */
    .sent-post-menu-wrap {
        position: relative;
    }

    .sent-post-menu-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.15s;
        padding: 0;
    }

    .sent-post-menu-btn:hover {
        background: #f9fafb;
        color: #374151;
    }

    .sent-post-menu-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 4px;
        min-width: 130px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        z-index: 50;
        padding: 4px 0;
    }

    .sent-post-menu-wrap.open .sent-post-menu-dropdown {
        display: block;
    }

    .sent-post-menu-item {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 8px 14px;
        border: none;
        background: none;
        color: #dc3545;
        font-size: 13px;
        cursor: pointer;
        transition: background 0.15s;
    }

    .sent-post-menu-item:hover {
        background: #fef2f2;
    }

    @media (max-width: 768px) {
        .sent-post-row {
            gap: 12px;
        }

        .sent-post-time-col {
            width: 55px;
        }

        .sent-card-body {
            flex-direction: column;
        }

        .sent-card-image {
            width: 100%;
            height: 160px;
        }

        .sent-card-stats {
            flex-wrap: wrap;
        }

        .sent-card-stat {
            min-width: 60px;
        }
    }

    @media (max-width: 576px) {
        .sent-post-row {
            flex-direction: column;
            gap: 6px;
        }

        .sent-post-time-col {
            width: auto;
            flex-direction: row;
            align-items: center;
            gap: 10px;
            padding-top: 0;
        }
    }

    /* --- Queue settings modal (redesign) --- */
    .queue-settings-modal-redesign .queue-settings-modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .queue-settings-modal-redesign .queue-settings-modal-header {
        padding: 20px 28px;
        border-bottom: 1px solid #f0f0f0;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .queue-settings-modal-redesign .queue-settings-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .queue-settings-modal-redesign .queue-settings-header-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .queue-settings-modal-redesign .queue-settings-modal-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
        letter-spacing: -0.01em;
    }

    .queue-settings-modal-redesign .queue-settings-modal-subtitle {
        font-size: 0.8125rem;
        color: #9ca3af;
        margin: 2px 0 0 0;
        font-weight: 400;
    }

    .queue-settings-modal-redesign .queue-settings-modal-close {
        width: 34px;
        height: 34px;
        padding: 0;
        border: none;
        border-radius: 10px;
        background: #f3f4f6;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .queue-settings-modal-redesign .queue-settings-modal-close:hover {
        background: #e5e7eb;
        color: #111827;
        transform: rotate(90deg);
    }

    .queue-settings-modal-redesign .queue-settings-modal-body {
        padding: 24px 28px;
        background: #f9fafb;
        max-height: 60vh;
        overflow-y: auto;
    }

    .queue-settings-modal-redesign .queue-settings-modal-body::-webkit-scrollbar {
        width: 5px;
    }

    .queue-settings-modal-redesign .queue-settings-modal-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .queue-settings-modal-redesign .queue-settings-modal-body::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 10px;
    }

    .queue-settings-modal-redesign .queue-settings-modal-body::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }

    .queue-settings-modal-redesign .queue-settings-info-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: linear-gradient(135deg, #eff6ff, #f0f5ff);
        border: 1px solid #dbeafe;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 0.8125rem;
        color: #3b82f6;
        line-height: 1.5;
    }

    .queue-settings-modal-redesign .queue-settings-info-bar i {
        font-size: 14px;
        flex-shrink: 0;
    }

    .queue-settings-modal-redesign .queue-settings-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .queue-settings-modal-redesign .queue-settings-item {
        background: #fff;
        border-radius: 14px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        border: 1px solid #e5e7eb;
        transition: all 0.25s ease;
    }

    .queue-settings-modal-redesign .queue-settings-item:hover {
        border-color: #c7d2fe;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.08);
    }

    .queue-settings-modal-redesign .queue-settings-account {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .queue-settings-modal-redesign .queue-settings-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .queue-settings-modal-redesign .queue-settings-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        object-fit: cover;
        border: 2.5px solid #f3f4f6;
        display: block;
        transition: border-color 0.2s ease;
    }

    .queue-settings-modal-redesign .queue-settings-item:hover .queue-settings-avatar {
        border-color: #e0e7ff;
    }

    .queue-settings-modal-redesign .queue-settings-platform-badge {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #fff;
        border: 2.5px solid #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }

    .queue-settings-modal-redesign .queue-settings-badge-facebook {
        background: #1877F2;
    }

    .queue-settings-modal-redesign .queue-settings-badge-pinterest {
        background: #E60023;
    }

    .queue-settings-modal-redesign .queue-settings-badge-tiktok {
        background: #010101;
    }

    .queue-settings-modal-redesign .queue-settings-account-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .queue-settings-modal-redesign .queue-settings-account-name {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #111827;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }

    .queue-settings-modal-redesign .queue-settings-account-type {
        font-size: 0.75rem;
        color: #9ca3af;
        font-weight: 500;
        margin-top: 1px;
    }

    .queue-settings-modal-redesign .queue-settings-hours {
        flex: 1;
        min-width: 200px;
        max-width: 340px;
    }

    .queue-settings-modal-redesign .queue-settings-hours-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .queue-settings-modal-redesign .queue-settings-hours-label i {
        font-size: 11px;
        color: #9ca3af;
    }

    .queue-settings-modal-redesign .queue-settings-select {
        border-radius: 10px;
        border: 1.5px solid #e5e7eb;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .queue-settings-modal-redesign .queue-settings-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .queue-settings-modal-redesign .queue-settings-shuffle {
        /* display: flex;
        align-items: center; */
        justify-items: anchor-center;
        text-align-last: center;
        gap: 12px;
        min-width: 140px;
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin: 0;
        white-space: nowrap;
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-label i {
        font-size: 11px;
        color: #9ca3af;
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #d1d5db;
        border-radius: 24px;
        transition: 0.3s;
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-slider::before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: #fff;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-input:checked+.queue-settings-shuffle-slider {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
    }

    .queue-settings-modal-redesign .queue-settings-shuffle-input:checked+.queue-settings-shuffle-slider::before {
        transform: translateX(20px);
    }

    .queue-settings-modal-redesign .queue-settings-modal-footer {
        padding: 18px 28px;
        border-top: 1px solid #f0f0f0;
        background: #fff;
        gap: 10px;
        flex-wrap: wrap;
        display: flex;
        justify-content: flex-end;
    }

    .queue-settings-modal-redesign .queue-settings-btn-cancel {
        border-radius: 10px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 9px 22px;
        color: #6b7280;
        background: #f3f4f6;
        border: 1.5px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .queue-settings-modal-redesign .queue-settings-btn-cancel:hover {
        background: #e5e7eb;
        color: #374151;
        border-color: #d1d5db;
    }

    .queue-settings-modal-redesign .queue-settings-btn-save {
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 9px 24px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border: none;
        color: #fff;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        transition: all 0.25s ease;
    }

    .queue-settings-modal-redesign .queue-settings-btn-save:hover {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        transform: translateY(-1px);
    }

    .queue-settings-modal-redesign .queue-settings-btn-save:active {
        transform: translateY(0);
    }

    @media (max-width: 576px) {
        .queue-settings-modal-redesign .queue-settings-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
        }

        .queue-settings-modal-redesign .queue-settings-shuffle {
            width: 100%;
        }

        .queue-settings-modal-redesign .queue-settings-hours {
            max-width: 100%;
            width: 100%;
        }

        .queue-settings-modal-redesign .queue-settings-modal-header {
            padding: 16px 20px;
        }

        .queue-settings-modal-redesign .queue-settings-modal-body {
            padding: 20px;
        }

        .queue-settings-modal-redesign .queue-settings-modal-footer {
            padding: 14px 20px;
        }

        .queue-settings-modal-redesign .queue-settings-account-name {
            max-width: 140px;
        }
    }

    /* --- Queue settings modal skeleton --- */
    .queue-settings-skeleton {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding: 4px 0;
    }

    .queue-settings-skeleton-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }

    .queue-settings-skeleton-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
        background-size: 200% 100%;
        animation: queue-settings-skeleton-shimmer 1.5s infinite;
        flex-shrink: 0;
    }

    .queue-settings-skeleton-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    .queue-settings-skeleton-line {
        height: 14px;
        border-radius: 6px;
        background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
        background-size: 200% 100%;
        animation: queue-settings-skeleton-shimmer 1.5s infinite;
    }

    .queue-settings-skeleton-name {
        width: 70%;
        max-width: 160px;
    }

    .queue-settings-skeleton-sub {
        width: 50%;
        max-width: 100px;
    }

    .queue-settings-skeleton-select {
        width: 140px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
        background-size: 200% 100%;
        animation: queue-settings-skeleton-shimmer 1.5s infinite;
        flex-shrink: 0;
    }

    @keyframes queue-settings-skeleton-shimmer {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    /* --- Custom Skeleton Styling --- */

    /* Animation Definition */
    @keyframes pulse-slow {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .5;
        }
    }

    .animate-pulse-slow {
        animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* --- Queue & Sent skeleton loaders --- */
    .queue-skeleton .skeleton-line,
    .sent-posts-skeleton .skeleton-line {
        background: #e5e7eb;
        border-radius: 4px;
        height: 12px;
    }

    .queue-skeleton .skeleton-time {
        width: 50px;
        height: 18px;
    }

    .queue-skeleton .skeleton-type {
        width: 60px;
        height: 10px;
        margin-top: 6px;
    }

    .queue-skeleton .skeleton-day-header {
        width: 140px;
        height: 16px;
    }

    .queue-skeleton .skeleton-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e5e7eb;
    }

    .queue-skeleton .skeleton-name {
        width: 80px;
        height: 14px;
        margin-left: 10px;
    }

    .queue-skeleton .skeleton-icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: #e5e7eb;
    }

    .queue-skeleton .skeleton-text {
        width: 70%;
        height: 14px;
        margin-bottom: 8px;
    }

    .queue-skeleton .skeleton-text-short {
        width: 40%;
        height: 14px;
    }

    .queue-skeleton .skeleton-image-sm {
        width: 120px;
        height: 120px;
        border-radius: 6px;
        background: #e5e7eb;
        flex-shrink: 0;
    }

    .queue-skeleton .skeleton-created {
        width: 120px;
        height: 12px;
        flex: 1;
    }

    .queue-skeleton .skeleton-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .queue-skeleton .skeleton-btn {
        width: 100px;
        height: 32px;
        border-radius: 6px;
        background: #e5e7eb;
    }

    .queue-skeleton .skeleton-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e5e7eb;
    }

    .queue-skeleton-row .queue-post-card-body {
        display: flex;
        gap: 16px;
        align-items: flex-start;
    }

    .queue-skeleton .queue-skeleton-body-text {
        flex: 1;
        min-width: 0;
    }

    .queue-skeleton-row .queue-post-account {
        display: flex;
        align-items: center;
    }

    .queue-skeleton-row .queue-post-card-footer {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sent-posts-skeleton .skeleton-time {
        width: 55px;
        height: 14px;
    }

    .sent-posts-skeleton .skeleton-type {
        width: 50px;
        height: 10px;
        margin-top: 6px;
    }

    .sent-posts-skeleton .skeleton-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e5e7eb;
    }

    .sent-posts-skeleton .skeleton-name {
        width: 90px;
        height: 14px;
        margin-left: 10px;
    }

    .sent-posts-skeleton .skeleton-title {
        width: 85%;
        height: 14px;
        margin: 12px 0 8px;
    }

    .sent-posts-skeleton .skeleton-title-short {
        width: 60%;
        height: 14px;
    }

    .sent-posts-skeleton .skeleton-image {
        width: 130px;
        height: 130px;
        border-radius: 8px;
        background: #e5e7eb;
        flex-shrink: 0;
    }

    .sent-posts-skeleton .skeleton-stat {
        width: 80px;
        height: 20px;
        border-radius: 4px;
        background: #e5e7eb;
    }

    .sent-skeleton-card .sent-card-stats {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f1f3f4;
    }

    .sent-skeleton-row .sent-card-account {
        display: flex;
        align-items: center;
    }

    /* Card Container (Main wrapper) */
    .card-container {
        width: 100%;
        max-width: 40rem;
        /* max-w-xl equivalent */
        background-color: white;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        border-radius: 0.75rem;
        /* rounded-xl */
        position: relative;
    }

    /* Skeleton Wrapper (Inner content box) */
    .skeleton-wrapper {
        display: flex;
        padding: 1.5rem;
        /* p-6 */
        background-color: #F3F4F6;
        /* skeleton-bg */
        border-radius: 0.75rem;
        overflow: hidden;
        /* Ensure rounded corners clip content */
    }

    /* Left Column (Text area) */
    .content-col {
        flex-grow: 1;
        padding-right: 1.5rem;
        /* pr-6 */
    }

    /* Right Column (Image/Sidebar block) */
    .image-col {
        width: 25%;
        /* w-1/4 */
        flex-shrink: 0;
        position: relative;
    }

    /* Skeleton Bars (The actual pulsing lines) */
    .skeleton-bar {
        background-color: #E5E7EB;
        /* skeleton-bar */
        border-radius: 0.25rem;
        /* rounded-sm to rounded-md */
        margin-bottom: 1rem;
        /* space-y-4 converted to margin-bottom */
    }

    /* Skeleton Bar Dimensions */
    .bar-title {
        height: 1.5rem;
        width: 75%;
        border-radius: 0.375rem;
    }

    /* h-6 w-3/4 */
    .bar-full {
        height: 1rem;
        width: 100%;
    }

    .bar-medium {
        height: 1rem;
        width: 85%;
    }

    /* w-5/6 */
    .bar-short {
        height: 1rem;
        width: 33.333%;
    }

    /* w-1/3 */
    .image-placeholder {
        height: 100%;
        width: 100%;
        border-radius: 0.5rem;
    }

    /* Close Button Styling */
    .close-btn-placeholder {
        position: absolute;
        top: -0.75rem;
        /* -top-3 */
        right: -0.75rem;
        /* -right-3 */
        width: 1.5rem;
        /* w-6 */
        height: 1.5rem;
        /* h-6 */
        background-color: black;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.75rem;
        /* text-xs */
        cursor: pointer;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Real Content Styling */
    .real-article-wrapper {
        display: flex;
        padding: 1.5rem;
        background-color: white;
        border-radius: 0.75rem;
        border: 1px solid #E5E7EB;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        color: #1F2937;
        opacity: 0;
        transition: opacity 1s;
    }

    .real-article-wrapper .title {
        font-size: 1.25rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
        color: #4338CA;
        /* Indigo-700 equivalent */
    }

    .real-article-wrapper .summary {
        font-size: 0.875rem;
        color: #4B5563;
        line-height: 1.625;
    }

    .real-article-wrapper img {
        width: 100%;
        height: auto;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Image Lightbox */
    .image-lightbox {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .image-lightbox.active {
        display: flex;
    }

    .lightbox-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        cursor: pointer;
    }

    .lightbox-content {
        position: relative;
        max-width: 90%;
        max-height: 90%;
        display: flex;
        flex-direction: column;
        align-items: center;
        animation: lightboxZoomIn 0.3s ease;
    }

    @keyframes lightboxZoomIn {
        from {
            opacity: 0;
            transform: scale(0.8);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .lightbox-content img {
        max-width: 100%;
        max-height: 80vh;
        border-radius: 8px;
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
        object-fit: contain;
    }

    .lightbox-close {
        position: absolute;
        top: -40px;
        right: -40px;
        width: 40px;
        height: 40px;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        font-size: 20px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lightbox-close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    .lightbox-caption {
        margin-top: 15px;
        color: #fff;
        font-size: 14px;
        text-align: center;
        max-width: 600px;
        line-height: 1.5;
    }

    /* Make post images clickable in DataTable */
    #postsTable .pinterest_card .image-container img.post-image,
    #postsTable .facebook_card .pronunciation-image-container img {
        cursor: zoom-in;
        transition: opacity 0.2s ease;
    }

    #postsTable .pinterest_card .image-container img.post-image:hover,
    #postsTable .facebook_card .pronunciation-image-container img:hover {
        opacity: 0.9;
    }

    /* Magnify icon on hover */
    #postsTable .pinterest_card .image-container,
    #postsTable .facebook_card .pronunciation-image-container {
        position: relative;
    }

    #postsTable .pinterest_card .image-container::before,
    #postsTable .facebook_card .pronunciation-image-container::before {
        content: '\f00e';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 50px;
        height: 50px;
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        opacity: 0;
        transition: opacity 0.2s ease;
        pointer-events: none;
        z-index: 10;
    }

    #postsTable .pinterest_card .image-container:hover::before,
    #postsTable .facebook_card .pronunciation-image-container:hover::before {
        opacity: 1;
    }

    @media (max-width: 768px) {
        .lightbox-close {
            top: 10px;
            right: 10px;
        }
    }

    /* Schedule Posts Grid Layout */
    .schedule-posts-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    @media (max-width: 1200px) {
        .schedule-posts-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .schedule-posts-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Schedule Post Card Container */
    .schedule-post-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 580px;
    }

    .schedule-post-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    /* Post Preview Section */
    .schedule-post-card .post-preview {
        height: 320px;
        overflow: hidden;
        position: relative;
    }

    .schedule-post-card .post-preview::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 30px;
        background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.9));
        pointer-events: none;
    }

    .schedule-post-card .post-preview .pinterest_card,
    .schedule-post-card .post-preview .facebook_card {
        margin: 0;
        border-radius: 0;
        box-shadow: none;
        height: 100%;
        overflow: hidden;
    }

    .schedule-post-card .post-preview .pinterest_card .image-container,
    .schedule-post-card .post-preview .facebook_card .pronunciation-image-container {
        max-height: 180px;
        overflow: hidden;
    }

    .schedule-post-card .post-preview .pinterest_card .image-container img,
    .schedule-post-card .post-preview .facebook_card .pronunciation-image-container img {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }

    /* Post Meta Section */
    .schedule-post-card .post-meta {
        padding: 12px 15px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow-y: auto;
    }

    .schedule-post-card .post-meta::-webkit-scrollbar {
        width: 4px;
    }

    .schedule-post-card .post-meta::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 2px;
    }

    .post-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .post-meta-row:last-child {
        margin-bottom: 0;
    }

    /* Account Badge */
    .post-account-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px;
        background: #fff;
        border-radius: 20px;
        border: 1px solid #e9ecef;
    }

    .post-account-badge img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
    }

    .post-account-badge .platform-icon {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #fff;
    }

    .post-account-badge .platform-icon.facebook {
        background: #1877F2;
    }

    .post-account-badge .platform-icon.pinterest {
        background: #E60023;
    }

    .post-account-badge .platform-icon.tiktok {
        background: #000000;
    }

    .post-account-badge .post-account-name {
        font-size: 12px;
        font-weight: 600;
        color: #333;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Source Badge */
    .source-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
    }

    .source-badge.rss {
        background: #fff3cd;
        color: #856404;
    }

    .source-badge.api {
        background: #e3f2fd;
        color: #1976d2;
    }

    .source-badge.manual {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .source-badge i {
        font-size: 10px;
    }

    /* Date/Time Info */
    .datetime-info {
        font-size: 11px;
        color: #666;
    }

    .datetime-info .label {
        color: #999;
        margin-right: 4px;
    }

    .datetime-info .value {
        font-weight: 500;
        color: #333;
    }

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge.published {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.failed {
        background: #f8d7da;
        color: #721c24;
    }

    /* Published At */
    .published-at {
        font-size: 10px;
        color: #28a745;
        background: #d4edda;
        padding: 2px 6px;
        border-radius: 3px;
        margin-top: 4px;
        display: inline-block;
    }

    /* Response Section */
    .response-section {
        margin-top: 8px;
        padding: 8px;
        background: #fff;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        max-height: 60px;
        overflow-y: auto;
    }

    .response-section::-webkit-scrollbar {
        width: 3px;
    }

    .response-section::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 2px;
    }

    .response-section .response-label {
        font-size: 10px;
        color: #999;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .response-section .response-text {
        font-size: 11px;
        color: #333;
        word-break: break-word;
        line-height: 1.3;
    }

    .response-section .response-text.success {
        color: #28a745;
    }

    .response-section .response-text.error {
        color: #dc3545;
    }

    /* Action Buttons */
    .post-actions-bar {
        display: flex;
        gap: 8px;
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #e9ecef;
    }

    .post-actions-bar .btn {
        flex: 1;
        padding: 6px 10px;
        font-size: 12px;
        border-radius: 6px;
    }

    /* Empty State */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    /* Pagination Styles */
    .pagination-info {
        font-size: 13px;
    }

    .pagination .page-item .page-link {
        border-radius: 6px;
        margin: 0 2px;
        border: none;
        color: #666;
    }

    .pagination .page-item.active .page-link {
        background: var(--theme-color);
        color: #fff;
    }

    .pagination .page-item.disabled .page-link {
        color: #ccc;
    }

    /* Make post images clickable in Grid */
    .schedule-post-card .pinterest_card .image-container img.post-image,
    .schedule-post-card .facebook_card .pronunciation-image-container img {
        cursor: zoom-in;
        transition: opacity 0.2s ease;
    }

    .schedule-post-card .pinterest_card .image-container img.post-image:hover,
    .schedule-post-card .facebook_card .pronunciation-image-container img:hover {
        opacity: 0.9;
    }

    /* Magnify icon on hover for Grid */
    .schedule-post-card .pinterest_card .image-container,
    .schedule-post-card .facebook_card .pronunciation-image-container {
        position: relative;
    }

    .schedule-post-card .pinterest_card .image-container::before,
    .schedule-post-card .facebook_card .pronunciation-image-container::before {
        content: '\f00e';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 50px;
        height: 50px;
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        opacity: 0;
        transition: opacity 0.2s ease;
        pointer-events: none;
        z-index: 10;
    }

    .schedule-post-card .pinterest_card .image-container:hover::before,
    .schedule-post-card .facebook_card .pronunciation-image-container:hover::before {
        opacity: 1;
    }

    /* Video Thumbnail Placeholder Styles */
    .video-thumbnail-placeholder {
        position: relative;
        width: 100%;
        min-height: 300px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .pinterest_card .video-thumbnail-placeholder {
        border-radius: 20px 20px 0px 0px;
    }

    .facebook_card .pronunciation-image-container.video-thumbnail-placeholder {
        position: relative;
        padding-top: 100%;
        min-height: auto;
    }

    .facebook_card .pronunciation-image-container.video-thumbnail-placeholder .video-placeholder-icon,
    .facebook_card .pronunciation-image-container.video-thumbnail-placeholder .video-play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .video-placeholder-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 64px;
        opacity: 0.8;
        z-index: 1;
    }

    .video-play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s ease;
        z-index: 2;
    }

    .video-thumbnail-placeholder:hover .video-play-overlay {
        background: rgba(0, 0, 0, 0.5);
    }

    .video-play-button {
        width: 70px;
        height: 70px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .video-play-button i {
        color: #333;
        font-size: 24px;
        margin-left: 4px;
    }

    .video-thumbnail-placeholder:hover .video-play-button {
        transform: scale(1.1);
        background: rgba(255, 255, 255, 1);
    }

    /* Action Dropdown Buttons Redesign */
    .action-buttons-container {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .action-dropdown-group {
        position: relative;
    }

    .btn-action-dropdown {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: 8px;
        border: 2px solid;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        position: relative;
        min-width: 140px;
    }

    .btn-action-dropdown:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-action-dropdown:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .btn-action-dropdown .dropdown-arrow {
        font-size: 0.75rem;
        transition: transform 0.3s ease;
        margin-left: 0.5rem;
    }

    .btn-action-dropdown[aria-expanded="true"] .dropdown-arrow {
        transform: rotate(180deg);
    }

    .btn-schedule {
        background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
        border-color: #dc3545;
        color: #dc3545;
    }

    .btn-schedule:hover {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
        border-color: #c82333;
        color: #c82333;
    }

    .btn-publish {
        background: linear-gradient(135deg, #fff 0%, #f0f7ff 100%);
        border-color: #007bff;
        color: #007bff;
    }

    .btn-publish:hover {
        background: linear-gradient(135deg, #f0f7ff 0%, #e0efff 100%);
        border-color: #0056b3;
        color: #0056b3;
    }

    .dropdown-menu-action {
        min-width: 200px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 0.5rem 0;
        margin-top: 0.5rem !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        background: #fff;
    }

    .dropdown-menu-action .dropdown-item {
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        font-weight: 500;
        color: #495057;
        transition: all 0.2s ease;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
    }

    .dropdown-menu-action .dropdown-item:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
        color: #212529;
        transform: translateX(4px);
    }

    .dropdown-menu-action .dropdown-item:active {
        background: #e9ecef;
        color: #212529;
    }

    .dropdown-menu-action .dropdown-item i {
        width: 20px;
        text-align: center;
        font-size: 0.9rem;
    }

    .dropdown-menu-action .dropdown-divider {
        margin: 0.5rem 0;
        border-top: 1px solid #e9ecef;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .action-buttons-container {
            flex-direction: column;
            width: 100%;
        }

        .action-dropdown-group {
            width: 100%;
        }

        .btn-action-dropdown {
            width: 100%;
            justify-content: space-between;
        }
    }

    /* --- Create Post Modal (100% match to reference design) --- */
    .create-post-modal.modal {
        display: flex !important;
        align-items: center;
        justify-content: center;
        min-height: 100%;
    }

    .create-post-modal .modal-dialog {
        width: 35vw;
        max-width: 35vw;
        max-height: calc(100vh - 2rem);
        margin: auto;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .create-post-modal-content {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #374151;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-height: 100%;
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
    }

    .create-post-modal-header {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }

    .create-post-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .create-post-modal-title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        letter-spacing: -0.01em;
    }

    .create-post-tags-dropdown {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: #4b5563;
        color: #d1d5db;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        cursor: pointer;
        border: none;
        transition: background 0.2s;
    }

    .create-post-tags-dropdown:hover {
        background: #6b7280;
        color: #fff;
    }

    .create-post-tags-icon {
        font-size: 12px;
        opacity: 0.9;
    }

    .create-post-tags-chevron {
        font-size: 10px;
        opacity: 0.8;
    }

    .create-post-header-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .create-post-header-icon-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: #6b7280;
        border-radius: 6px;
        cursor: pointer;
        transition: color 0.2s, background 0.2s;
    }

    .create-post-header-icon-btn:hover {
        color: #1f2937;
        background: rgba(0, 0, 0, 0.05);
    }

    .create-post-modal-body {
        position: relative;
        padding: 20px 24px 24px;
        background: #fff;
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .create-post-body-close-btn {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: #6b7280;
        border-radius: 6px;
        cursor: pointer;
        transition: color 0.2s, background 0.2s;
        z-index: 10;
    }

    .create-post-body-close-btn:hover {
        color: #1f2937;
        background: rgba(0, 0, 0, 0.05);
    }

    .create-post-channels-row {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .create-post-channels-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        padding: 0;
        background: #e5e7eb;
        color: #4b5563;
        font-size: 14px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .create-post-channels-btn:hover {
        background: #d1d5db;
        color: #374151;
    }

    .create-post-channels-btn i {
        font-size: 12px;
    }

    .create-post-channels-dropdown-wrap {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .create-post-selected-channels {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .create-post-selected-channel-chip {
        /* padding: 3px; */
        /* border: 1px solid #e5e7eb; */
        width: 40px;
        height: 40px;
        overflow: visible;
        position: relative;
        flex-shrink: 0;
        cursor: pointer;
    }

    .create-post-selected-channel-chip:hover {
        cursor: pointer;
    }

    .create-post-selected-channel-chip-remove {
        position: absolute;
        top: -4px;
        right: -4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #374151;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
        z-index: 2;
        border: 2px solid #fff;
    }

    .create-post-selected-channel-chip:hover .create-post-selected-channel-chip-remove {
        opacity: 1;
    }

    .create-post-selected-channel-chip-remove:hover {
        background: #ef4444;
    }

    .create-post-selected-channel-chip.has-tooltip {
        position: relative;
        overflow: visible;
    }

    .create-post-selected-channel-chip.has-tooltip::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%) translateY(-5px);
        padding: 6px 10px;
        background: #333;
        color: #fff;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        border-radius: 6px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        visibility: hidden;
        min-width: max-content;
    }

    .create-post-selected-channel-chip.has-tooltip::before {
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
        transition: opacity 0.2s ease, visibility 0.2s ease;
        z-index: 10001;
        visibility: hidden;
    }

    .create-post-selected-channel-chip.has-tooltip:hover::after,
    .create-post-selected-channel-chip.has-tooltip:hover::before {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .create-post-selected-channel-chip.has-tooltip:hover::before {
        transform: translateX(-50%) translateY(0);
    }

    .create-post-selected-channel-chip-inner {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        overflow: visible;
        position: relative;
        display: block;
    }

    .create-post-selected-channel-chip img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        border-radius: 10px;
    }

    .create-post-selected-channel-chip .create-post-chip-badge {
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #fff;
        border: 2px solid #fff;
    }

    .create-post-selected-channel-chip .create-post-chip-badge.facebook {
        background: #1877f2;
    }

    .create-post-selected-channel-chip .create-post-chip-badge.pinterest {
        background: #e60023;
    }

    .create-post-selected-channel-chip .create-post-chip-badge.tiktok {
        background: #000;
    }

    .create-post-channels-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 8px;
        width: 420px;
        max-height: 400px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        z-index: 1050;
    }

    .create-post-channels-dropdown.is-open {
        display: block;
    }

    .channels-dropdown-search {
        display: flex;
        align-items: center;
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
    }

    .channels-dropdown-search-icon {
        color: #9ca3af;
        font-size: 14px;
        margin-right: 10px;
    }

    .channels-dropdown-search-input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 14px;
        color: #374151;
    }

    .channels-dropdown-search-input::placeholder {
        color: #9ca3af;
    }

    .channels-dropdown-search-input:focus {
        outline: none;
    }

    .channels-dropdown-search:focus-within {
        box-shadow: 0 0 0 2px #22c55e;
        border-radius: 8px;
    }

    .channels-dropdown-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: #fff;
    }

    .channels-dropdown-title {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
        letter-spacing: 0.02em;
    }

    .channels-dropdown-deselect {
        background: none;
        border: none;
        font-size: 13px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        text-decoration: none;
    }

    .channels-dropdown-deselect:hover {
        color: #22c55e;
    }

    .channels-dropdown-list {
        max-height: 280px;
        overflow-y: auto;
    }

    .channels-dropdown-item {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        gap: 12px;
        cursor: pointer;
        transition: background 0.15s;
    }

    .channels-dropdown-item:hover {
        background: #f5f5f4;
    }

    .channels-dropdown-item-avatar {
        position: relative;
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        border-radius: 8px;
        overflow: visible;
    }

    .channels-dropdown-item-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        object-fit: cover;
        object-position: center;
        /* background: #e5e7eb; */
        display: block;
    }

    .channels-dropdown-item-badge {
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #fff;
        border: 2px solid #fff;
    }

    .channels-dropdown-item-badge.facebook {
        background: #1877f2;
    }

    .channels-dropdown-item-badge.pinterest {
        background: #e60023;
    }

    .channels-dropdown-item-badge.tiktok {
        background: #000;
    }

    .channels-dropdown-item-name {
        flex: 1;
        font-size: 14px;
        color: #374151;
        font-weight: 500;
    }

    .channels-dropdown-item-checkbox {
        width: 20px;
        height: 20px;
        margin: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #d1d5db;
        border-radius: 4px;
        background: #fff;
        transition: border-color 0.2s, background 0.2s;
    }

    .channels-dropdown-item-checkbox:hover {
        border-color: #9ca3af;
    }

    .channels-dropdown-checkbox {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .channels-dropdown-checkbox-icon {
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .channels-dropdown-checkbox:checked+.channels-dropdown-checkbox-icon {
        display: flex;
        color: #fff;
    }

    .channels-dropdown-item-checkbox:has(.channels-dropdown-checkbox:checked) {
        background: #22c55e;
        border-color: #22c55e;
    }

    .create-post-last-used {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 3px 12px 3px 14px;
        background: #fff;
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }

    .create-post-last-used:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    .create-post-last-used-label {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
    }

    .create-post-last-used-avatar-wrap {
        position: relative;
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    .create-post-last-used-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        filter: grayscale(100%);
        display: block;
    }

    .create-post-last-used-avatar-wrap .create-post-last-used-badge {
        position: absolute;
        bottom: 0px;
        right: 0px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #4b5563;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: #fff;
        border: 2px solid #fff;
    }

    .create-post-last-used-avatar-wrap .create-post-last-used-badge.facebook {
        background: #1877f2;
    }

    .create-post-last-used-avatar-wrap .create-post-last-used-badge.pinterest {
        background: #e60023;
    }

    .create-post-last-used-avatar-wrap .create-post-last-used-badge.tiktok {
        background: #000;
    }

    .create-post-main-content {
        flex: 1;
        min-height: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: auto;
    }

    .create-post-main-content.has-editor {
        align-items: stretch;
        justify-content: flex-start;
        padding: 20px;
    }

    .create-post-main-content.has-editor .create-post-editor-wrap {
        flex: 1;
        min-height: stretch;
    }

    .create-post-empty-state {
        width: 100%;
        text-align: center;
        padding: 40px 24px;
    }

    .create-post-empty-message {
        /* margin: 0; */
        font-size: 15px;
        color: #6b7280;
        line-height: 1.5;
        /* max-width: 360px; */
    }

    .create-post-editor-wrap {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: space-between;
        flex-direction: column;
        gap: 20px;
        min-height: 0;
        overflow-y: auto;
    }

    .create-post-editor-textarea {
        width: 100%;
        flex: 0 0 auto;
        min-height: 120px;
        padding: 0;
        font-size: 16px;
        line-height: 1.5;
        color: #1f2937;
        background: transparent;
        border: none;
        outline: none;
        resize: none;
    }

    .create-post-editor-textarea::placeholder {
        color: #9ca3af;
    }

    .create-post-link-preview {
        flex-shrink: 0;
        width: 100%;
        display: block;
        margin: 12px 0;
    }

    .create-post-link-preview .skeleton-wrapper,
    .create-post-link-preview .real-article-wrapper {
        display: flex !important;
        padding: 12px 16px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        margin-bottom: 12px;
        min-height: 80px;
        opacity: 1 !important;
        visibility: visible !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .create-post-link-preview:not(:empty) {
        margin-bottom: 4px;
    }

    .create-post-link-preview .skeleton-bar {
        background: #e5e7eb;
        border-radius: 4px;
        margin-bottom: 8px;
    }

    .create-post-link-preview .bar-title {
        height: 1rem;
        width: 75%;
    }

    .create-post-link-preview .bar-full {
        height: 0.75rem;
        width: 100%;
    }

    .create-post-link-preview .image-placeholder {
        height: 60px;
        width: 80px;
        border-radius: 6px;
    }

    .create-post-link-preview .content-col {
        flex-grow: 1;
    }

    .create-post-link-preview .image-col {
        width: 80px;
        flex-shrink: 0;
        position: relative;
    }

    .create-post-link-preview .link_title {
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 4px;
    }

    .create-post-link-preview .link_url {
        font-size: 12px;
        color: #6b7280;
        margin: 0;
        word-break: break-all;
    }

    .create-post-link-preview img {
        width: 100%;
        height: auto;
        border-radius: 6px;
    }

    .create-post-link-preview .close-btn-placeholder {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 24px;
        height: 24px;
        background: #374151;
        color: #fff;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .create-post-comment-input {
        width: 100%;
        padding: 12px 16px;
        font-size: 14px;
        line-height: 1.5;
        color: #1f2937;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        resize: vertical;
        /* min-height: 64px; */
        flex-shrink: 0;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .create-post-comment-input::placeholder {
        color: #94a3b8;
    }

    .create-post-comment-input:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .create-post-comment-input:focus {
        outline: none;
        border-color: #22c55e;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
    }

    .create-post-editor-bottom {
        flex-shrink: 0;
        /* display: flex;
        align-items: flex-end;
        justify-content: space-between; */
        gap: 16px;
        flex-wrap: wrap;
    }

    .create-post-upload-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 200px;
        min-height: 120px;
        padding: 20px;
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        background: #fafafa;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        gap: 12px;
    }

    .create-post-upload-zone:hover {
        border-color: #22c55e;
        background: #f0fdf4;
    }

    .create-post-upload-zone.is-dragover {
        border-color: #22c55e;
        background: #f0fdf4;
    }

    .create-post-facebook-format-wrap {
        width: 100%;
        margin-block: 12px;
        /* padding: 10px 12px;
        border-radius: 8px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb; */
        display: block;
    }

    .create-post-facebook-format-label {
        display: none;
    }

    .create-post-facebook-format-bar {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .create-post-facebook-format-facebook-icon {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: #1877F2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 14px;
        flex-shrink: 0;
    }

    .create-post-facebook-format-radios {
        display: flex;
        flex-wrap: nowrap;
        gap: 18px;
        align-items: center;
        justify-content: flex-start;
    }

    .create-post-format-option {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        font-weight: 500;
        user-select: none;
    }

    .create-post-format-option input {
        display: none;
    }

    .create-post-format-option span {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .create-post-format-option span::before {
        content: '';
        width: 14px;
        height: 14px;
        border-radius: 999px;
        border: 2px solid #9ca3af;
        background: #ffffff;
        box-sizing: border-box;
        display: inline-block;
        flex-shrink: 0;
    }

    .create-post-format-option input:checked+span::before {
        border-color: #22c55e;
        background: #22c55e;
        box-shadow: inset 0 0 0 3px #f3f4f6;
    }

    .create-post-upload-previews {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
        max-width: 100%;
    }

    .create-post-upload-previews:not(:empty)+.create-post-upload-prompt .create-post-upload-text {
        font-size: 13px;
    }

    .create-post-upload-preview {
        position: relative;
        width: 100px;
        height: 100PX;
        border-radius: 6px;
        overflow: hidden;
        flex-shrink: 0;
        background: #e5e7eb;
    }

    .create-post-upload-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .create-post-upload-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .create-post-video-upload-state {
        position: absolute;
        left: 8px;
        right: 8px;
        bottom: 8px;
        background: rgba(15, 23, 42, 0.82);
        border-radius: 8px;
        padding: 6px 8px;
        z-index: 2;
    }

    .create-post-video-upload-progress {
        width: 100%;
        height: 6px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.25);
        overflow: hidden;
    }

    .create-post-video-upload-progress-fill {
        display: block;
        width: 0;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #3B82F6, #60A5FA);
        transition: width 120ms linear;
    }

    .create-post-video-upload-percent {
        margin-top: 4px;
        font-size: 11px;
        color: #FFFFFF;
        text-align: right;
        font-weight: 600;
    }

    .create-post-upload-preview.is-video-uploaded .create-post-video-upload-state {
        background: rgba(5, 150, 105, 0.88);
    }

    .create-post-upload-preview .create-post-upload-preview-icon {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 24px;
    }

    .create-post-upload-preview-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        padding: 0;
        line-height: 1;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .create-post-upload-preview:hover .create-post-upload-preview-remove {
        opacity: 1;
    }

    .create-post-upload-preview-remove:hover {
        background: #ef4444;
    }

    .create-post-upload-prompt {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .create-post-upload-icon {
        font-size: 28px;
        color: #9ca3af;
        margin-bottom: 8px;
    }

    .create-post-upload-zone:hover .create-post-upload-icon {
        color: #22c55e;
    }

    .create-post-upload-text {
        margin: 0;
        font-size: 14px;
        color: #6b7280;
    }

    .create-post-upload-link {
        color: #22c55e;
        text-decoration: underline;
        cursor: pointer;
        font-weight: 500;
    }

    .create-post-upload-link:hover {
        color: #16a34a;
    }

    .create-post-editor-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        width: 100%;
    }

    .create-post-comment-wrap .create-post-comment-hr {
        width: 100%;
        margin: 0 0 8px;
        border: none;
        border-top: 1px solid #e5e7eb;
    }

    .create-post-editor-actions .create-post-comment-wrap {
        width: 100%;
    }

    .create-post-action-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: #6b7280;
        border-radius: 6px;
        cursor: pointer;
        transition: color 0.2s, background 0.2s;
    }

    .create-post-action-btn:hover {
        color: #1f2937;
        background: rgba(0, 0, 0, 0.05);
    }

    .create-post-emoji-trigger-wrap {
        position: relative;
    }

    .create-post-emoji-picker-wrap {
        position: absolute;
        bottom: 100%;
        left: 0;
        margin-bottom: 8px;
        z-index: 1060;
        display: none;
    }

    .create-post-emoji-picker-wrap.is-open {
        display: block;
    }

    .create-post-emoji-picker-wrap emoji-picker {
        --background: #fff;
        --border-color: #e5e7eb;
        --border-radius: 12px;
        --input-border-color: #e5e7eb;
        --input-font-size: 14px;
        --outline-size: 0;
        --num-columns: 8;
        --shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
    }

    .create-post-first-comment-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 200px;
    }

    .create-post-first-comment-label {
        font-size: 14px;
        font-weight: 400;
        color: #333333;
        margin: 0;
        flex-shrink: 0;
    }

    .create-post-first-comment-input {
        flex: 1;
        min-width: 0;
        padding: 10px 14px;
        font-size: 14px;
        line-height: 1.5;
        color: #1f2937;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .create-post-first-comment-input::placeholder {
        color: #94a3b8;
    }

    .create-post-first-comment-input:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .create-post-first-comment-input:focus {
        outline: none;
        border-color: #22c55e;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
    }

    .create-post-modal-footer {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        background: #fff;
        border-top: 1px solid #e5e7eb;
    }

    .create-post-footer-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .create-post-checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        cursor: pointer;
        font-size: 14px;
        color: #9ca3af;
        font-weight: 400;
    }

    .create-post-checkbox-label:hover {
        color: #d1d5db;
    }

    .create-post-checkbox {
        width: 16px;
        height: 16px;
        accent-color: #6b7280;
        cursor: pointer;
    }

    .create-post-footer-divider {
        width: 1px;
        height: 20px;
        background: #4b5563;
    }

    .create-post-save-draft-btn {
        background: none;
        border: none;
        color: #9ca3af;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        padding: 0;
        transition: color 0.2s;
    }

    .create-post-save-draft-btn:hover {
        color: #fff;
    }

    .create-post-footer-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .create-post-segmented-buttons {
        display: inline-flex;
        align-items: stretch;
        border: 1px solid #d1d5db;
        border-radius: 24px;
        overflow: hidden;
        background: #fff;
    }

    .create-post-segmented-btn {
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        background: #fff;
        border: none;
        border-right: 1px solid #e5e7eb;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .create-post-segmented-btn:last-child {
        border-right: none;
    }

    .create-post-segmented-btn:hover {
        background: #f3f4f6;
        color: #1f2937;
    }

    .create-post-segmented-btn-primary {
        background: #d1fae5;
        color: #065f46;
    }

    .create-post-segmented-btn-primary:hover {
        background: #a7f3d0;
        color: #047857;
    }

    .create-post-footer-actions-wrap {
        position: relative;
    }

    .create-post-schedule-dropdown {
        display: none;
        position: absolute;
        bottom: 100%;
        left: -45px;
        margin-bottom: 8px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        padding: 16px;
        min-width: 280px;
        z-index: 1050;
    }

    .create-post-schedule-dropdown.is-open {
        display: block;
    }

    .create-post-schedule-picker {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .create-post-schedule-row {
        display: flex;
        gap: 12px;
        align-items: flex-end;
    }

    .create-post-schedule-field {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .create-post-schedule-field label {
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
    }

    .create-post-schedule-input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }

    .create-post-schedule-confirm-btn {
        padding: 8px 16px;
        background: #3b82f6;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }

    .create-post-schedule-confirm-btn:hover {
        background: #2563eb;
    }

    /* --- Post Comment Modal (matches Create Post modal design) --- */
    .post-comment-modal .modal-dialog {
        width: 480px;
        max-width: 90vw;
        margin: auto;
    }

    .post-comment-modal-content {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #374151;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
    }

    .post-comment-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }

    .post-comment-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .post-comment-modal-title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        letter-spacing: -0.01em;
    }

    .post-comment-header-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .post-comment-header-icon-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: #6b7280;
        border-radius: 6px;
        cursor: pointer;
        transition: color 0.2s, background 0.2s;
    }

    .post-comment-header-icon-btn:hover {
        color: #1f2937;
        background: rgba(0, 0, 0, 0.05);
    }

    .post-comment-modal-body {
        padding: 20px 24px 24px;
        background: #fff;
        flex: 1;
    }

    .post-comment-description {
        margin: 0 0 12px;
        font-size: 14px;
        color: #6b7280;
        line-height: 1.5;
    }

    .post-comment-textarea {
        width: 100%;
        padding: 12px 14px;
        font-size: 14px;
        color: #374151;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        resize: vertical;
        min-height: 100px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .post-comment-textarea:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
    }

    .post-comment-textarea::placeholder {
        color: #9ca3af;
    }

    .post-comment-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        background: #fff;
        border-top: 1px solid #e5e7eb;
    }

    .post-comment-footer-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .post-comment-footer-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .post-comment-btn {
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        cursor: pointer;
        transition: background 0.2s, color 0.2s, border-color 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .post-comment-btn-secondary {
        background: #fff;
        color: #374151;
    }

    .post-comment-btn-secondary:hover {
        background: #f3f4f6;
        color: #1f2937;
    }

    .post-comment-btn-primary {
        background: #d1fae5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    .post-comment-btn-primary:hover {
        background: #a7f3d0;
        color: #047857;
        border-color: #6ee7b7;
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 700px) {
        .queue-post-card-footer {
            flex-direction: column;
            align-items: flex-start;
        }

        .queue-post-actions {
            order: 1;
        }

        .queue-post-created {
            order: 2;
            flex: none;
        }
    }

    @media (max-width: 768px) {
        .page-content {
            height: calc(100vh - 100px);
        }

        .queue-timeslots-section,
        .posts-grid-section {
            padding: 12px 16px;
        }

        .selected-account-action-btn {
            padding: 8px 12px;
            min-height: 44px;
        }

        .accounts-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }

        .account-card,
        .posts-status-tab,
        .queue-timeslots-new-btn,
        .create-post-segmented-btn {
            min-height: 44px;
        }

        .queue-post-chat-btn,
        .create-post-action-btn {
            min-width: 44px;
            min-height: 44px;
        }

        .queue-post-edit-btn,
        .queue-post-more-btn {
            min-width: 30px;
            min-height: 30px;
        }

        .create-post-modal .modal-dialog {
            width: 95vw;
            max-width: none;
            margin: 1rem auto;
            max-height: calc(100vh - 2rem);
        }
    }

    @media (max-width: 576px) {
        .page-content {
            height: calc(100vh - 56px);
        }

        .page-content>.content {
            padding: 0 0.25rem;
        }

        .queue-timeslots-section,
        .posts-grid-section {
            padding: 12px;
        }

        .queue-timeslots-row {
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }

        .queue-timeslots-time-col {
            width: 100%;
            padding-top: 0;
        }

        .queue-post-card-body {
            flex-direction: column;
            gap: 12px;
        }

        .queue-post-image-wrap {
            width: 100%;
            max-width: 200px;
            height: auto;
            aspect-ratio: 1;
            margin-left: 0;
        }

        .selected-account-info {
            flex-wrap: wrap;
        }

        .selected-account-name {
            font-size: 14px;
        }

        .accounts-grid {
            grid-template-columns: 1fr;
        }

        /* .create-post-modal .modal-dialog {
            width: 100%;
            margin: 0;
            max-height: 100vh;
            border-radius: 0;
        } */

        /* .create-post-modal-content {
            border-radius: 0;
        } */

        .create-post-segmented-buttons {
            flex-wrap: wrap;
            border-radius: 8px;
        }

        .create-post-segmented-btn {
            flex: 1 1 45%;
            min-width: 120px;
        }

        .create-post-schedule-dropdown {
            left: 0;
            right: 0;
            min-width: auto;
            width: 100%;
        }
    }

    /* Accounts sidebar: visible in flow at all breakpoints (no mobile hide) */
    @media (max-width: 1024px) {
        .accounts-sidebar {
            width: 220px;
        }

        .accounts-sidebar.collapsed {
            width: 4rem;
        }
    }

    @media (max-width: 576px) {
        .accounts-sidebar {
            width: 180px;
        }

        .accounts-sidebar.collapsed .account-card {
            padding: 2px;
        }

        .accounts-sidebar.collapsed {
            width: 3.5rem;
        }
    }

    @media (max-width: 1280px) {

        .queue-timeslots-section,
        .posts-grid-section {
            padding: 16px 5%;
        }

        .create-post-modal .modal-dialog {
            width: 90vw;
            max-width: 480px;
        }
    }

    /* Mobile toggle and backdrop no longer used - sidebar always visible */
    .accounts-sidebar-mobile-toggle,
    .accounts-sidebar-backdrop {
        display: none !important;
    }

    /* new post modal responsive */
    @media (min-width: 576px) {
        #createPostModal .modal-dialog-centered {
            min-height: calc(100% - 10rem);
        }
    }

    @media (max-width: 576px) {
        #createPostModal .modal-dialog-centered {
            min-height: calc(100% - 10rem);
        }
    }

    /* new post modal responsive */
</style>
