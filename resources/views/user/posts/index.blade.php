@extends('user.layout.main')
@section('title', 'Posts')
@section('page_content')
    <div class="page-content">
        @include('user.layout.feature-limit-alert')
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="schedule-page-wrapper">
                    {{-- Accounts sidebar (closed by default: profile pic + platform icon only) --}}
                    <aside id="accounts-sidebar" class="accounts-sidebar collapsed">
                        <div class="accounts-sidebar-sticky">
                            <button type="button" class="accounts-sidebar-search-icon" id="sidebarSearchIcon"
                                aria-label="Search accounts" title="Search accounts">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="accounts-sidebar-toggle" id="sidebarCollapseBtn"
                                aria-label="Collapse sidebar" title="Collapse sidebar">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="accounts-sidebar-search-wrap" id="sidebarSearchWrap">
                                <div class="accounts-sidebar-search-box">
                                    <i class="fas fa-search accounts-sidebar-search-icon-inner"></i>
                                    <input type="text" id="accountSearchInput" class="accounts-sidebar-search-input"
                                        placeholder="Search accounts..." autocomplete="off">
                                    <button type="button" class="accounts-sidebar-search-clear" id="accountSearchClear"
                                        style="display:none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="accounts-container">
                            <div class="accounts-grid">
                                {{-- All Channels (always present, selected by default) --}}
                                <div class="account-card all-channels-card active" data-type="all" data-id="all">
                                    <div class="account-card-inner">
                                        <div class="account-avatar">
                                            <div class="all-channels-icon">
                                                <span></span><span></span><span></span><span></span>
                                            </div>
                                        </div>
                                        <div class="account-details">
                                            <span class="account-name">All Channels</span>
                                        </div>
                                    </div>
                                </div>
                                @foreach ($accounts as $account)
                                    @if ($account->type == 'facebook')
                                        <div class="account-card active" data-type="{{ $account->type }}"
                                            data-id="{{ $account->id }}">
                                            <div class="account-card-inner">
                                                <div class="account-avatar">
                                                    <img src="{{ $account->profile_image }}"
                                                        onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';"
                                                        loading="lazy">
                                                    <span class="platform-badge facebook">
                                                        <i class="fab fa-facebook-f"></i>
                                                    </span>
                                                </div>
                                                <div class="account-details">
                                                    <span class="account-name">{{ Str::limit($account->name, 18) }}</span>
                                                    <span
                                                        class="account-username">{{ Str::limit($account->facebook?->username, 15) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </aside>
                    <div class="schedule-main-content">
                        <div class="card">
                            <div class="card-body">
                                {{-- Selected account header + post type tabs (sticky when scrolling) --}}
                                <div class="selected-account-container">
                                    <div class="selected-account-sticky-wrap">
                                        <div id="selected-account-header" class="selected-account-header"
                                            style="display: none;">
                                            <div class="selected-account-info">
                                                <div class="selected-account-avatar-wrap" id="selected-account-avatar-wrap">
                                                    <img id="selected-account-header-img" class="selected-account-avatar"
                                                        src="" alt="" loading="lazy">
                                                    <span id="selected-account-header-badge"
                                                        class="selected-account-platform-badge facebook"><i
                                                            class="fab fa-facebook-f"></i></span>
                                                </div>
                                                <div class="selected-account-allch-icon" id="selected-account-allch-icon"
                                                    style="display:none;">
                                                    <span></span><span></span><span></span><span></span>
                                                </div>
                                                <div class="selected-account-text">
                                                    <span id="selected-account-header-name"
                                                        class="selected-account-name"></span>
                                                    <button type="button" class="selected-account-header-settings-btn"
                                                        id="selected-account-header-settings"
                                                        aria-label="Queue settings for this account" title="Queue settings">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="selected-account-actions">
                                                <button type="button"
                                                    class="selected-account-action-btn selected-account-view-list is-active"
                                                    data-view="list" title="List view" aria-label="List view">
                                                    <i class="fas fa-list-ul"></i>
                                                    <span>List</span>
                                                </button>
                                                <button type="button"
                                                    class="selected-account-action-btn selected-account-new-post"
                                                    title="New post" aria-label="New post">
                                                    <i class="fas fa-plus"></i>
                                                    <span>New Post</span>
                                                </button>

                                            </div>
                                        </div>
                                        {{-- Posts status tabs (Queue, Sent, Failed) --}}
                                        <div id="posts-status-tabs" class="posts-status-tabs" style="display: none;">
                                            <button type="button" class="posts-status-tab is-active" data-tab="queue"
                                                aria-selected="true">
                                                <span class="posts-status-tab-label">Queue</span>
                                                <span class="posts-status-tab-badge" data-count="queue">0</span>
                                            </button>
                                            <button type="button" class="posts-status-tab" data-tab="sent"
                                                aria-selected="false">
                                                <span class="posts-status-tab-label">Sent</span>
                                                <span class="posts-status-tab-badge" data-count="sent">0</span>
                                            </button>
                                            <button type="button" class="posts-status-tab" data-tab="failed"
                                                aria-selected="false">
                                                <span class="posts-status-tab-label">Failed</span>
                                                <span class="posts-status-tab-badge" data-count="failed">0</span>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Queue tab: timeslots section (selected account's queue settings) --}}
                                    <div id="queue-timeslots-section" class="queue-timeslots-section"
                                        style="display: none;">
                                        <div id="queue-timeslots-content" class="queue-timeslots-content">
                                            {{-- Filled by JS: date groups (Today, Tomorrow, ...) and time rows with + New --}}
                                        </div>
                                        <div id="queue-timeslots-empty" class="queue-timeslots-empty"
                                            style="display: none;">
                                            <div class="empty-state-box">
                                                <i class="far fa-folder-open"></i>
                                                <p class="queue-timeslots-empty-text">No queued posts found.</p>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Sent / Failed posts grid (scrollable) --}}
                                    <div id="postsGrid" class="posts-grid-section" style="display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('user.schedule.modals.settings-modal')
    @include('user.schedule.modals.schedule-modal')
    @include('user.schedule.modals.edit-post-modal')
    @include('user.schedule.modals.tiktok-post-modal')

    {{-- Image Lightbox Modal --}}
    <div class="image-lightbox" id="imageLightbox">
        <div class="lightbox-backdrop"></div>
        <div class="lightbox-content">
            <button class="lightbox-close" id="lightboxClose">
                <i class="fas fa-times"></i>
            </button>
            <img src="" alt="Full size image" id="lightboxImage" loading="lazy">
            <div class="lightbox-caption" id="lightboxCaption"></div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- styling --}}
    @include('user.posts.assets.style')
    @include('user.schedule.assets.facebook_post')
    @include('user.schedule.assets.pinterest_post')
@endpush

@push('scripts')
    {{-- scripts --}}
    @include('user.posts.assets.script')
@endpush
