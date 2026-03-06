@extends('user.layout.main')
@section('title', 'Schedule')
@section('page_content')
    <div class="page-content">
        @include('user.layout.feature-limit-alert')
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="schedule-page-wrapper">
                    {{-- Accounts sidebar (closed by default: profile pic + platform icon only) --}}
                    <aside id="accounts-sidebar" class="accounts-sidebar collapsed">
                        <button type="button" class="accounts-sidebar-toggle" aria-label="Toggle accounts">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button" class="accounts-sidebar-search-icon" id="sidebarSearchIcon"
                            aria-label="Search accounts" title="Search accounts">
                            <i class="fas fa-search"></i>
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
                        @if (count($accounts) > 0)
                            <div class="accounts-container">
                                <div class="accounts-grid">
                                    @foreach ($accounts as $account)
                                        @if ($account->type == 'facebook')
                                            <div class="account-card @if ($account->schedule_status == 'active') active @endif"
                                                data-type="{{ $account->type }}" data-id="{{ $account->id }}">
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
                                                        <span
                                                            class="account-name">{{ Str::limit($account->name, 18) }}</span>
                                                        <span
                                                            class="account-username">{{ Str::limit($account->facebook?->username, 15) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($account->type == 'pinterest')
                                            <div class="account-card @if ($account->schedule_status == 'active') active @endif"
                                                data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                                <div class="account-card-inner">
                                                    <div class="account-avatar">
                                                        <img src="{{ $account->pinterest?->profile_image }}"
                                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';"
                                                            loading="lazy">
                                                        <span class="platform-badge pinterest">
                                                            <i class="fab fa-pinterest-p"></i>
                                                        </span>
                                                    </div>
                                                    <div class="account-details">
                                                        <span
                                                            class="account-name">{{ Str::limit($account->name, 18) }}</span>
                                                        <span
                                                            class="account-username">{{ Str::limit($account->pinterest?->username, 15) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($account->type == 'tiktok')
                                            <div class="account-card @if ($account->schedule_status == 'active') active @endif"
                                                data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                                <div class="account-card-inner">
                                                    <div class="account-avatar">
                                                        <img src="{{ $account->profile_image }}"
                                                            onerror="this.onerror=null; this.src='{{ social_logo('tiktok') }}';"
                                                            loading="lazy">
                                                        <span class="platform-badge tiktok">
                                                            <i class="fab fa-tiktok"></i>
                                                        </span>
                                                    </div>
                                                    <div class="account-details">
                                                        <span
                                                            class="account-name">{{ Str::limit($account->name, 18) }}</span>
                                                        <span
                                                            class="account-username">{{ Str::limit($account->username, 15) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
                                                <div class="selected-account-avatar-wrap">
                                                    <img id="selected-account-header-img" class="selected-account-avatar"
                                                        src="" alt="" loading="lazy">
                                                    <span id="selected-account-header-badge"
                                                        class="selected-account-platform-badge facebook"><i
                                                            class="fab fa-facebook-f"></i></span>
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
                                                <div class="selected-account-action-chip selected-account-timezone-wrap"
                                                    title="Your timezone">
                                                    <i class="fas fa-clock"></i>
                                                    <span
                                                        class="selected-account-timezone-text">{{ $userTimezoneName ?? 'UTC' }}</span>
                                                </div>
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
                                            <p class="queue-timeslots-empty-text">No posting hours set. Open Queue settings
                                                to choose hours for this account.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body px-0">
                                    <div class="row">
                                        <textarea name="content" id="content" class="form-control col-md-12 check_count"
                                            placeholder="Paste your link here!" rows="3" data-max="100"></textarea>
                                        <span id="characterCount" class="text-muted"></span>
                                    </div>
                                    <div id="article-container" class="card-container"></div>
                                    <div class="row">
                                        <div class="form-control col-md-12 dropzone" id="dropZone">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <textarea name="comment" id="comment" class="form-control col-md-12" placeholder="Comment here!" rows="1"
                                            data-max="100"></textarea>
                                    </div>
                                    <div class="row justify-content-between mt-2">
                                        <div>
                                            <button type="button" class="btn btn-outline-info btn-sm setting_btn">
                                                SETTINGS
                                            </button>
                                        </div>
                                        <div class="d-flex action-buttons-container" style="gap: 0.75rem;">
                                            <div>
                                                <button type="button" class="btn btn-outline-danger action_btn"
                                                    href="schedule">
                                                    Schedule
                                                </button>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-outline-success action_btn"
                                                    href="queue">
                                                    Queue
                                                </button>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-outline-primary action_btn"
                                                    href="publish">
                                                    Publish Now
                                                </button>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-outline-info action_btn"
                                                    href="draft">
                                                    Draft
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header with-border clearfix">
                                <div class="card-title">
                                    <span>Posts</span>
                                </div>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                        title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row m-0 p-0 mb-4">
                                    <div class="col-md-6">
                                        <label for="filter_post_type">Post Type</label>
                                        <select name="filter_post_type" id="filter_post_type"
                                            class="form-control select2 filter" multiple>
                                            <option value="photo">Image</option>
                                            <option value="content_only">Quote</option>
                                            <option value="link">Link</option>
                                            <option value="video">Video</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="filter_status">Status</label>
                                        <select name="filter_status" id="filter_status" class="form-control filter">
                                            <option value="all" selected>All Status</option>
                                            <option value="0">Pending</option>
                                            <option value="1">Published</option>
                                            <option value="-1">Failed</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Posts Grid --}}
                                <div id="postsGrid" class="schedule-posts-grid">
                                    <div class="loading-state text-center py-5">
                                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                        <p class="mt-2 text-muted">Loading posts...</p>
                                    </div>
                                </div>

                                {{-- Pagination --}}
                                <div id="postsPagination" class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="pagination-info text-muted"></div>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0"></ul>
                                    </nav>
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
    @include('user.schedule.assets.style')
    @include('user.schedule.assets.facebook_post')
    @include('user.schedule.assets.pinterest_post')
@endpush

@push('scripts')
    {{-- scripts --}}
    @include('user.schedule.assets.script')
@endpush
