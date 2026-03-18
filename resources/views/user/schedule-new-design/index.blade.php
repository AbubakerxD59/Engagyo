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
                                @php
                                    $hasSelectedAccount = false;
                                    $selectedAccountId = null;
                                    $saved = $scheduleSelectedAccount ?? [];
                                    if (!empty($saved['type']) && !empty($saved['id']) && ($saved['type'] ?? '') !== 'all') {
                                        $sid = (string) $saved['id'];
                                        $accountExists = $accounts->contains(fn($a) => (string) $a->id === $sid && ($a->type ?? '') === ($saved['type'] ?? ''));
                                        if ($accountExists) {
                                            $hasSelectedAccount = true;
                                            $selectedAccountId = $sid;
                                        }
                                    }
                                @endphp
                                {{-- All Channels: active only when no specific account is saved --}}
                                <div class="account-card all-channels-card {{ !$hasSelectedAccount ? 'active' : '' }}" data-type="all" data-id="all">
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
                                        <div class="account-card {{ ($hasSelectedAccount && $selectedAccountId == (string) $account->id) ? 'active' : (!$hasSelectedAccount ? 'active' : '') }}" data-type="{{ $account->type }}"
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
                    <div id="sidebarBackdrop" class="accounts-sidebar-backdrop" aria-hidden="true"></div>
                    <div class="schedule-main-content">
                        <button type="button" class="accounts-sidebar-mobile-toggle" id="sidebarMobileToggle"
                            aria-label="Open channels" title="Open channels" style="display: none;">
                            <i class="fas fa-bars"></i>
                        </button>
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
                                                    <div class="selected-account-header-buttons">
                                                        <button type="button" class="selected-account-header-settings-btn"
                                                            id="selected-account-header-settings"
                                                            aria-label="Queue settings for this account" title="Queue settings">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <button type="button" class="selected-account-header-refresh-btn"
                                                            id="selected-account-header-refresh"
                                                            aria-label="Refresh posts and insights" title="Refresh posts and insights">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                        <span class="selected-account-header-sync-msg" id="selected-account-header-sync-msg" style="display: none;" aria-live="polite">Posts and insights are being synced…</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="selected-account-actions">
                                                <button type="button"
                                                    class="selected-account-action-btn selected-account-new-post"
                                                    title="New post" aria-label="New post">
                                                    <i class="fas fa-plus"></i>
                                                    <span>New Post</span>
                                                </button>

                                            </div>
                                        </div>
                                        {{-- Posts status tabs (Queue, Sent) + Search bar --}}
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
                                            <div id="posts-search-wrap" class="posts-search-wrap" style="display: none;">
                                                <div class="posts-search-inner">
                                                    <span class="posts-search-icon"><i class="fas fa-search"></i></span>
                                                    <input type="text" id="postsSearchInput" class="posts-search-input"
                                                        placeholder="Search posts by title..." autocomplete="off">
                                                    <button type="button" id="postsSearchClear" class="posts-search-clear"
                                                        aria-label="Clear search" style="display: none;">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
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
                                    {{-- Sent posts grid (scrollable) --}}
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
    @include('user.schedule-new-design.modals.create-post-modal')
    @include('user.schedule.modals.edit-post-modal')
    @include('user.schedule.modals.tiktok-post-modal')

    {{-- Post Comment Modal (design matches Create Post modal) --}}
    <div class="modal fade post-comment-modal" id="postCommentModal" tabindex="-1" role="dialog" aria-labelledby="postCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered post-comment-modal-dialog" role="document">
            <div class="modal-content post-comment-modal-content">
                <div class="post-comment-modal-header">
                    <div class="post-comment-header-left">
                        <h5 class="post-comment-modal-title" id="postCommentModalLabel">Post Comment</h5>
                    </div>
                    <div class="post-comment-header-actions">
                        <button type="button" class="post-comment-header-icon-btn post-comment-close-btn" data-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="post-comment-modal-body">
                    <p class="post-comment-description">This comment will be posted with your post when it is published.</p>
                    <textarea id="postCommentInput" class="post-comment-textarea" rows="4" placeholder="Add a comment..."></textarea>
                </div>
                <div class="post-comment-modal-footer">
                    <div class="post-comment-footer-left"></div>
                    <div class="post-comment-footer-right">
                        <button type="button" class="post-comment-btn post-comment-btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="post-comment-btn post-comment-btn-primary" id="postCommentSaveBtn">
                            <i class="fas fa-save"></i> Save Comment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    @include('user.schedule-new-design.assets.style')
    @include('user.schedule.assets.facebook_post')
    @include('user.schedule.assets.pinterest_post')
@endpush

@push('scripts')
    {{-- scripts --}}
    @include('user.schedule-new-design.assets.script')
    <script type="module">
        (async function() {
            await import('https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js');
            const wrap = document.getElementById('createPostEmojiPickerWrap');
            const btn = document.getElementById('createPostEmojiBtn');
            const postTextarea = document.getElementById('createPostEditorTextarea');
            const commentTextarea = document.getElementById('createPostComment');
            const firstCommentInput = document.getElementById('createPostFirstComment');
            if (!wrap || !btn || !postTextarea) return;
            const textInputs = [postTextarea, commentTextarea, firstCommentInput].filter(Boolean);
            let lastFocusedInput = postTextarea;
            textInputs.forEach(function(el) {
                el.addEventListener('focus', function() { lastFocusedInput = el; });
            });
            const picker = document.createElement('emoji-picker');
            wrap.appendChild(picker);
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                wrap.classList.toggle('is-open');
            });
            function insertEmojiAtCursor(target, emoji) {
                const start = target.selectionStart;
                const end = target.selectionEnd;
                const text = target.value;
                target.value = text.substring(0, start) + emoji + text.substring(end);
                target.selectionStart = target.selectionEnd = start + emoji.length;
                target.focus();
            }
            picker.addEventListener('emoji-click', function(e) {
                const emoji = (e.detail.unicode || (e.detail.emoji && e.detail.emoji.unicode)) || '';
                if (!emoji) return;
                const target = textInputs.indexOf(document.activeElement) >= 0 ? document.activeElement : lastFocusedInput;
                insertEmojiAtCursor(target, emoji);
            });
            document.addEventListener('click', function(e) {
                if (!wrap.contains(e.target) && !btn.contains(e.target)) {
                    wrap.classList.remove('is-open');
                }
            });
            $('#createPostModal').on('hidden.bs.modal', function() {
                wrap.classList.remove('is-open');
            });
        })();
    </script>
@endpush
