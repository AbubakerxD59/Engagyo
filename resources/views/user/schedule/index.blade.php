@extends('user.layout.main')
@section('title', 'Schedule')
@section('page_content')
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
                                                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';" loading="lazy">
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
                                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';" loading="lazy">
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
                                                            onerror="this.onerror=null; this.src='{{ social_logo('tiktok') }}';" loading="lazy">
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
                            {{-- URL shortener when link is in content text but post is photo/content (no link preview) --}}
                            <div id="content-url-shortener-wrap" class="row mt-2" style="display: none;">
                                <div class="col-12">
                                    <label class="d-flex align-items-center mb-1">
                                        <input type="checkbox" id="use_short_link_content" name="use_short_link_content"
                                            class="mr-2">
                                        <span>Shorten link for this post</span>
                                    </label>
                                    <div id="short-link-result-content" class="mt-1" style="display:none;">
                                        <label class="small text-muted mb-0">Shortened link:</label>
                                        <div class="input-group input-group-sm mt-1">
                                            <input type="text" id="short_link_url_display_content" class="form-control"
                                                readonly>
                                            <div class="input-group-append">
                                                <button type="button"
                                                    class="btn btn-outline-secondary copy-short-link-content"
                                                    title="Copy">Copy</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
