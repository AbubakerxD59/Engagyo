@extends('user.layout.main')
@section('title', 'Schedule')
@section('critical_css')
    <style>
        @keyframes schedule-shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }
        .schedule-sk-base {
            background: linear-gradient(90deg, #e9ecef 0%, #f4f6f8 45%, #e9ecef 90%);
            background-size: 200% 100%;
            animation: schedule-shimmer 1.25s ease-in-out infinite;
            border-radius: 8px;
        }
        #schedule-page-skeleton {
            min-height: 72vh;
            background: #f4f6f9;
        }
        .schedule-page-skeleton-inner .schedule-sk-card-shell {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .schedule-sk-card-header.schedule-sk-line-lg {
            height: 22px;
            max-width: 220px;
            border-radius: 6px;
        }
        .schedule-sk-account-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .schedule-sk-account-pill {
            width: 168px;
            height: 64px;
            border-radius: 10px;
        }
        .schedule-sk-line {
            height: 14px;
            width: 100%;
            border-radius: 6px;
        }
        .schedule-sk-textarea {
            height: 88px;
            width: 100%;
        }
        .schedule-sk-dropzone {
            height: 120px;
            width: 100%;
            border-radius: 10px;
        }
        .schedule-sk-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .schedule-sk-btn {
            width: 108px;
            height: 36px;
            border-radius: 6px;
        }
        .schedule-sk-label {
            height: 12px;
            width: 88px;
        }
        .schedule-sk-line.h-38 { height: 38px; }
        .schedule-sk-line.w-35 { width: 35%; }
        .schedule-sk-line.w-45 { width: 45%; }
        .schedule-sk-line.w-60 { width: 60%; }
        .schedule-sk-line.w-85 { width: 85%; }
        .schedule-post-skeleton-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 580px;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        .schedule-post-skeleton-preview {
            height: 320px;
            width: 100%;
            border-radius: 0;
            animation: schedule-shimmer 1.25s ease-in-out infinite;
        }
        .schedule-post-skeleton-meta {
            padding: 14px 16px;
            background: #f8f9fa;
            flex: 1;
        }
        .schedule-page-skeleton-inner .schedule-posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 1200px) {
            .schedule-page-skeleton-inner .schedule-posts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .schedule-page-skeleton-inner .schedule-posts-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .schedule-sk-base,
            .schedule-post-skeleton-preview {
                animation: none;
                background: #e9ecef;
            }
        }
    </style>
@endsection
@section('page_content')
    <div id="schedule-page-skeleton" class="schedule-page-skeleton-outer" aria-busy="true" aria-label="Loading schedule">
        @include('user.schedule.partials.schedule-page-skeleton-inner')
    </div>

    <div id="schedule-page-root" hidden>
    <div class="page-content">
        @include('user.layout.feature-limit-alert')
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <span>Schedule</span>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('panel.schedule.new-design') }}" class="btn btn-sm btn-outline-primary mr-2">
                                <i class="fas fa-paint-brush mr-1"></i> Beta version
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (count($accounts) > 0)
                            <div class="accounts-container">
                                <div class="accounts-grid">
                                    @foreach ($accounts as $account)
                                        @if ($account->type == 'facebook')
                                            <div class="account-card has-tooltip @if ($account->schedule_status == 'active') active @endif"
                                                data-type="{{ $account->type }}" data-id="{{ $account->id }}"
                                                data-tooltip="{{ $account->facebook?->username }}">
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
                                            <div class="account-card has-tooltip @if ($account->schedule_status == 'active') active @endif"
                                                data-type="{{ $account->type }}" data-id="{{ $account->id }}"
                                                data-tooltip="{{ $account->pinterest?->username }}">
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
                                            <div class="account-card has-tooltip @if ($account->schedule_status == 'active') active @endif"
                                                data-type="{{ $account->type }}" data-id="{{ $account->id }}"
                                                data-tooltip="{{ $account->username }}">
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
                        <div class="card-body px-0">
                            <div class="row">
                                <textarea name="content" id="content" class="form-control col-md-12 check_count" placeholder="Paste your link here!"
                                    rows="3" data-max="100"></textarea>
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
                                        <button type="button" class="btn btn-outline-danger action_btn" href="schedule">
                                            Schedule
                                        </button>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-success action_btn" href="queue">
                                            Queue
                                        </button>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary action_btn" href="publish">
                                            Publish Now
                                        </button>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-info action_btn" href="draft">
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
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row m-0 p-0 mb-4">
                            <div class="col-md-6">
                                <label for="filter_post_type">Post Type</label>
                                <select name="filter_post_type" id="filter_post_type" class="form-control select2 filter"
                                    multiple>
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

                        {{-- Posts Grid (skeleton until AJAX loads; same markup as template below) --}}
                        <div id="postsGrid" class="schedule-posts-grid">
                            @include('user.schedule.partials.posts-grid-skeleton-items')
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

    <template id="schedule-posts-skeleton-template">
        @include('user.schedule.partials.posts-grid-skeleton-items')
    </template>
    </div>{{-- /#schedule-page-root --}}

    <script>
        (function () {
            function revealScheduleShell() {
                var sk = document.getElementById('schedule-page-skeleton');
                var root = document.getElementById('schedule-page-root');
                if (sk) {
                    sk.style.display = 'none';
                    sk.removeAttribute('aria-busy');
                }
                if (root) {
                    root.removeAttribute('hidden');
                    root.setAttribute('aria-hidden', 'false');
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', revealScheduleShell);
            } else {
                revealScheduleShell();
            }
        })();
    </script>
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
