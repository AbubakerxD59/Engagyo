@extends('user.layout.main')
@section('title', 'Automation')
@section('page_content')
    <div class="page-content">
        @include('user.layout.feature-limit-alert')
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <span>Rss Feed</span>
                        </div>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="fetchPostForm">
                            <div class="row justify-content-end">
                                @php
                                    $selectedType = $selectedAccount = '';
                                    $selectedAccountDomains = [];
                                @endphp
                                <div class="col-md-4 form-group mb-0">
                                    <label for="fetch_account">Accounts</label>
                                    <select name="account" id="account" class="form-control adv_filter">
                                        <option value="">All Accounts</option>
                                        @foreach ($accounts as $key => $account)
                                            <option value="{{ $account->id }}" data-type="{{ $account->type }}"
                                                data-shuffle="{{ $account->shuffle }}"
                                                data-rss-paused="{{ $account->rss_paused ? 1 : 0 }}"
                                                @php $selected = false;
                                                    if(@$user->rss_filters['selected_account'] == $account->id && @$user->rss_filters['selected_type'] == $account->type){
                                                        $selected = true;
                                                        $selectedType = $account->type;
                                                        $selectedAccount = $account->id;
                                                        $selectedAccountDomains = $user->getDomains($selectedAccount, $selectedType);
                                                    } @endphp
                                                @selected($selected)>
                                                {{ strtoupper($account->name . ' -  ' . $account->type . " ($account->account_name)") }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- @forelse ($selectedAccountDomains as $index => $domain)
                                    <div class="col-md-8 row url_body">
                                        <div class="col-md-6 form-group mb-0">
                                            <label for="time">Time <span class="text-danger">*</span></label>
                                            <select name="time[]" class="form-control select2 time_dropdown" multiple
                                                required>
                                                @foreach ($timeslots as $timeslot)
                                                    <option value="{{ $timeslot }}" @selected(in_array($timeslot, $domain->time))>
                                                        {{ $timeslot }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 form-group mb-0">
                                            <label for="feed_url">Feed Url <span class="text-danger">*</span></label>
                                            @if ($selectedAccount && $selectedType)
                                                <div class="row col-md-12 d-flex justify-content-between form-group">
                                                    <input type="text" value="{{ $domain->name }}" name="feed_url[]"
                                                        class="col-md-10 form-control mb-2" required>
                                                    <div class="row">
                                                        <button type="button"
                                                            class="btn btn-outline-success btn-sm ml-2 fetch_url_btn">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-outline-danger btn-sm ml-2 delete_domain_btn"
                                                            data-url-id="{{ $domain->id }}"
                                                            title="Delete Selected Domains">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-md-8 row url_body">
                                        <div class="col-md-6 form-group mb-0">
                                            <label for="time">Time <span class="text-danger">*</span></label>
                                            <select name="time[]" class="form-control select2 time_dropdown" multiple
                                                required>
                                                @foreach ($timeslots as $timeslot)
                                                    <option value="{{ $timeslot }}">
                                                        {{ $timeslot }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 form-group mb-0">
                                            <label for="feed_url">Feed Url <span class="text-danger">*</span></label>
                                            <div class="row col-md-12 d-flex justify-content-between form-group">
                                                <input type="text" name="feed_url[]" class="col-md-10 form-control mb-2"
                                                    required>
                                                <div class="row">
                                                    <button type="button"
                                                        class="btn btn-outline-success btn-sm ml-2 fetch_url_btn">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforelse --}}
                            </div>
                            <div class="row justify-content-end new_url_section" style="display: none;"></div>
                            <div class="row justify-content-end">
                                <button type="button" class="btn btn-info mx-2 add_new_url" id="addNewUrl">
                                    + Add New
                                </button>
                                <button type="submit" class="btn btn-outline-primary" id="fetchPostsBtn">
                                    Fetch All
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                {{-- Posts table --}}
                <div class="card">
                    <div class="card-header with-border">
                        <div class="card-title">
                            <span><i class="fas fa-filter mr-2"></i>Accounts</span>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Filters Section --}}
                        <form id="adv_filter_form" class="automation-filters">
                            <div class="row justify-content-between align-items-end">
                                <div class="col-md-4 form-group mb-0">
                                    <label for="status" class="filter-label">
                                        <i class="fas fa-info-circle mr-1"></i>Status
                                    </label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="1">Published</option>
                                        <option value="0" selected>Pending</option>
                                        <option value="-1">Failed</option>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-center col-md-4 form-group mb-0">
                                    <div class="toggle-control-item shuffle_toggle col-md-6 d-flex justify-content-between"
                                        style="display: none;">
                                        <label for="toggle" class="toggle-label-text">
                                            <i class="fas fa-random mr-1"></i>Shuffle
                                        </label>
                                        <div class="toggle-switch">
                                            <input class="toggle-input shuffle" id="toggle" type="checkbox">
                                            <label class="toggle-label" for="toggle"></label>
                                        </div>
                                    </div>
                                    <div class="toggle-control-item rss_toggle col-md-6 d-flex justify-content-between"
                                        style="display: none;">
                                        <label for="rss_toggle" class="toggle-label-text">
                                            <i class="fas fa-rss mr-1"></i>RSS Automation
                                        </label>
                                        <div class="toggle-switch">
                                            <input class="toggle-input rss_automation" id="rss_toggle" type="checkbox">
                                            <label class="toggle-label rss-label" for="rss_toggle"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        {{-- Control Panel Section --}}
                        <div class="automation-control-panel">
                            <div class="col-md-6 row">
                                <button id="clearFilters" class="btn btn-secondary">
                                    <i class="fas fa-eraser mr-1"></i>
                                    Clear Filters
                                </button>
                                <button id="deleteAll" class="btn btn-danger" style="display: none;">
                                    <i class="fas fa-trash-alt mr-1"></i>
                                    Delete All
                                </button>
                            </div>
                            <div class="col-md-6 row justify-content-end">
                                <div class="info-badge">
                                    <i class="fas fa-clock mr-1"></i>
                                    <span class="info-label">Last Fetch:</span>
                                    <span class="info-value last_fetch">NA</span>
                                </div>
                                <div class="info-badge">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    <span class="info-label">Scheduled Till:</span>
                                    <span class="info-value scheduled_till">NA</span>
                                </div>
                            </div>
                        </div>

                        {{-- Posts Grid --}}
                        <div id="postsGrid" class="automation-posts-grid">
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
    @include('user.automation.edit_post_modal')

    {{-- Image Lightbox Modal --}}
    <div class="image-lightbox" id="imageLightbox">
        <div class="lightbox-backdrop"></div>
        <div class="lightbox-content">
            <button class="lightbox-close" id="lightboxClose">
                <i class="fas fa-times"></i>
            </button>
            <img src="" alt="Full size image" id="lightboxImage">
            <div class="lightbox-caption" id="lightboxCaption"></div>
        </div>
    </div>
@endsection
@push('styles')
    @include('user.schedule.assets.facebook_post')
    @include('user.schedule.assets.pinterest_post')
    <style>
        /* Automation Filters Section */
        .automation-filters {
            padding: 5px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filter-label {
            font-weight: 600;
            color: #495057;
            font-size: 13px;
            margin-bottom: 6px;
            display: block;
        }

        .filter-label i {
            color: #6c757d;
        }

        /* Control Panel Section */
        .automation-control-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 20px;
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            flex-wrap: wrap;
            gap: 20px;
        }

        .automation-control-panel button {
            color: white !important;
        }

        .control-panel-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .control-panel-right {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* Action Buttons Group */
        .action-buttons-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .action-buttons-group .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-buttons-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Toggle Controls Group */
        .toggle-controls-group {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .toggle-control-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .toggle-control-item:hover {
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .toggle-label-text {
            font-weight: 600;
            color: #495057;
            font-size: 13px;
            margin: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .toggle-label-text i {
            color: #6c757d;
        }

        /* Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .toggle-switch .toggle-input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-switch .toggle-label {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 26px;
        }

        .toggle-switch .toggle-label:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch .toggle-input:checked+.toggle-label {
            background-color: #28a745;
        }

        .toggle-switch .toggle-input:checked+.toggle-label:before {
            transform: translateX(24px);
        }

        .toggle-switch .toggle-input:focus+.toggle-label {
            box-shadow: 0 0 1px #28a745;
        }

        /* Info Badges */
        .info-badges {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .info-badge {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-size: 13px;
            white-space: nowrap;
        }

        .info-badge i {
            color: #667eea;
            margin-right: 6px;
        }

        .info-label {
            color: #6c757d;
            font-weight: 500;
            margin-right: 6px;
        }

        .info-value {
            color: #495057;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .automation-control-panel {
                flex-direction: column;
                align-items: stretch;
            }

            .control-panel-left,
            .control-panel-right {
                width: 100%;
                justify-content: center;
            }

            .toggle-controls-group {
                flex-wrap: wrap;
                justify-content: center;
            }

            .info-badges {
                justify-content: center;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .automation-filters {
                padding: 15px;
            }

            .automation-control-panel {
                padding: 15px;
            }

            .action-buttons-group {
                flex-direction: column;
                width: 100%;
            }

            .action-buttons-group .btn {
                width: 100%;
            }

            .toggle-control-item {
                flex: 1;
                justify-content: space-between;
            }

            .info-badge {
                flex: 1;
                min-width: 100%;
            }
        }

        /* Automation Posts Grid Layout */
        .automation-posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .automation-posts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .automation-posts-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Automation Post Card Container */
        .automation-post-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 580px;
        }

        .automation-post-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        /* Post Preview Section */
        .automation-post-card .post-preview {
            height: 320px;
            overflow: hidden;
            position: relative;
        }

        .automation-post-card .post-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.9));
            pointer-events: none;
        }

        .automation-post-card .post-preview .pinterest_card,
        .automation-post-card .post-preview .facebook_card {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
            height: 100%;
            overflow: hidden;
        }

        .automation-post-card .post-preview .pinterest_card .image-container,
        .automation-post-card .post-preview .facebook_card .pronunciation-image-container {
            max-height: 180px;
            overflow: hidden;
        }

        .automation-post-card .post-preview .pinterest_card .image-container img,
        .automation-post-card .post-preview .facebook_card .pronunciation-image-container img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        /* Post Meta Section */
        .automation-post-card .post-meta {
            padding: 12px 15px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow-y: auto;
        }

        .automation-post-card .post-meta::-webkit-scrollbar {
            width: 4px;
        }

        .automation-post-card .post-meta::-webkit-scrollbar-thumb {
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

        /* Domain Badge */
        .domain-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .domain-badge i {
            font-size: 10px;
            flex-shrink: 0;
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

        /* Make post images clickable */
        .automation-post-card .pinterest_card .image-container img.post-image,
        .automation-post-card .facebook_card .pronunciation-image-container img {
            cursor: zoom-in;
            transition: opacity 0.2s ease;
        }

        .automation-post-card .pinterest_card .image-container img.post-image:hover,
        .automation-post-card .facebook_card .pronunciation-image-container img:hover {
            opacity: 0.9;
        }

        /* Magnify icon on hover */
        .automation-post-card .pinterest_card .image-container,
        .automation-post-card .facebook_card .pronunciation-image-container {
            position: relative;
        }

        .automation-post-card .pinterest_card .image-container::before,
        .automation-post-card .facebook_card .pronunciation-image-container::before {
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

        .automation-post-card .pinterest_card .image-container:hover::before,
        .automation-post-card .facebook_card .pronunciation-image-container:hover::before {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .lightbox-close {
                top: 10px;
                right: 10px;
            }
        }
    </style>
@endpush
@push('scripts')
    <script>
        // Posts Grid Variables
        var currentPage = 1;
        var perPage = 9;
        var totalPosts = 0;

        // Load posts
        function loadPosts(page = 1) {
            currentPage = page;

            $('#postsGrid').html(`
                <div class="loading-state text-center py-5" style="grid-column: 1/-1;">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">Loading posts...</p>
                </div>
            `);

            $.ajax({
                url: "{{ route('panel.automation.posts.dataTable') }}",
                type: "GET",
                data: {
                    draw: 1,
                    start: (page - 1) * perPage,
                    length: perPage,
                    account: $("#account").find(":selected").val(),
                    account_type: $("#account").find(":selected").data("type") ?? 0,
                    status: $("#status").find(":selected").val(),
                },
                success: function(response) {
                    totalPosts = response.iTotalDisplayRecords;
                    renderPosts(response.data);
                    renderPagination();
                    // Update scheduled till and last fetch
                    $('.scheduled_till').html(response.scheduled_till || 'NA');
                    $('.last_fetch').html(!empty(response.last_fetch) ? response.last_fetch : 'NA');
                },
                error: function() {
                    $('#postsGrid').html(`
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Failed to load posts. Please try again.</p>
                        </div>
                    `);
                }
            });
        }

        // Render posts grid
        function renderPosts(posts) {
            if (posts.length === 0) {
                $('#postsGrid').html(`
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No RSS posts found</p>
                        <small>Fetch posts from RSS feeds using the form above</small>
                    </div>
                `);
                return;
            }

            var html = '';
            posts.forEach(function(post) {
                html += renderPostCard(post);
            });
            $('#postsGrid').html(html);
        }

        // Render single post card
        function renderPostCard(post) {
            var statusClass = post.status == 1 ? 'published' : (post.status == -1 ? 'failed' : 'pending');
            var statusText = post.status == 1 ? 'Published' : (post.status == -1 ? 'Failed' : 'Pending');
            var platformIcon = post.social_type === 'facebook' ? 'fab fa-facebook-f' : (post.social_type === 'pinterest' ?
                'fab fa-pinterest-p' : 'fab fa-tiktok');
            var platformClass = post.social_type;

            // Domain badge
            var domainBadge = '';
            if (post.domain_name) {
                domainBadge =
                    `<span class="domain-badge" title="${post.domain_name}"><i class="fas fa-globe"></i> ${post.domain_name}</span>`;
            }

            var publishedAt = post.status == 1 && post.published_at_formatted ?
                `<div class="published-at">Published at: ${post.published_at_formatted}</div>` : '';

            var responseHtml = '';
            if (post.response) {
                var responseClass = post.status == 1 ? 'success' : (post.status == -1 ? 'error' : '');
                var responseText = post.response_message;
                // var responseText = post.response.length > 100 ? post.response.substring(0, 100) + '...' : post.response;
                responseHtml = `
                    <div class="response-section">
                        <div class="response-label">Response</div>
                        <div class="response-text ${responseClass}">${responseText}</div>
                    </div>
                `;
            }

            var actionButtons = '';
            if (post.status == 0) {
                actionButtons = `
                    <button class="btn btn-outline-primary btn-sm edit_btn" data-id="${post.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-warning btn-sm fix-post" data-post-id="${post.id}" title="Fix Post">
                        <i class="fas fa-wrench"></i>
                    </button>
                    <button class="btn btn-outline-success btn-sm publish-post" data-id="${post.id}" data-type="${post.social_type}" title="Publish Now">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm delete_btn" data-id="${post.id}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            } else {
                actionButtons = `
                    <button class="btn btn-outline-danger btn-sm delete_btn" data-id="${post.id}" title="Delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                `;
            }

            return `
                <div class="automation-post-card">
                    <div class="post-preview">
                        ${post.post_details}
                    </div>
                    <div class="post-meta">
                        <div class="post-meta-row">
                            <div class="post-account-badge">
                                <span class="platform-icon ${platformClass}">
                                    <i class="${platformIcon}"></i>
                                </span>
                                <span class="post-account-name">${post.account_name || 'Unknown'}</span>
                            </div>
                            ${domainBadge}
                        </div>
                        <div class="post-meta-row">
                            <div class="datetime-info">
                                <span class="label">Scheduled:</span>
                                <span class="value">${post.publish_datetime}</span>
                            </div>
                            <div>
                                <span class="status-badge ${statusClass}">
                                    <i class="fas fa-${post.status == 1 ? 'check-circle' : (post.status == -1 ? 'times-circle' : 'clock')}"></i>
                                    ${statusText}
                                </span>
                                ${publishedAt}
                            </div>
                        </div>
                        ${responseHtml}
                        <div class="post-actions-bar">
                            ${actionButtons}
                        </div>
                    </div>
                </div>
            `;
        }

        // Render pagination
        function renderPagination() {
            var totalPages = Math.ceil(totalPosts / perPage);
            var start = (currentPage - 1) * perPage + 1;
            var end = Math.min(currentPage * perPage, totalPosts);

            if (totalPosts === 0) {
                $('.pagination-info').html('');
                $('.pagination').html('');
                return;
            }

            $('.pagination-info').html(`Showing ${start} to ${end} of ${totalPosts} posts`);

            var paginationHtml = '';

            // Previous button
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;

            // Page numbers
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                if (startPage > 2) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (var i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                paginationHtml +=
                    `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
            }

            // Next button
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;

            $('.pagination').html(paginationHtml);
        }

        // Pagination click
        $(document).on('click', '.pagination .page-link', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                loadPosts(page);
            }
        });

        $('#status').on('change', function() {
            loadPosts(1);
        });
        // Reload posts function (for use after actions)
        function reloadPosts() {
            loadPosts(currentPage);
        }

        // Initial load
        loadPosts(1);
    </script>
    <script>
        $(document).ready(function() {
            // multiple fetch feed posts
            $("#fetchPostForm").on('submit', function(event) {
                event.preventDefault();
                var form = $(this);
                var submit_botton = form.find('#fetchPostsBtn');
                fetchPosts('multiple', submit_botton);
            });
            // single fetch feed posts
            $(document).on('click', '.fetch_url_btn', function() {
                var submit_botton = $(this);
                fetchPosts('single', submit_botton);
            });
            // fetch posts function
            function fetchPosts(type = 'single', submit_button) {
                if (type == 'single') {
                    var url_body = submit_button.closest('.url_body');
                } else {
                    var url_body = $('.url_body');
                }
                // validate post
                var is_valid = true;
                var selected_account = $("#account").find(":selected").val();
                var selected_type = $("#account").find(":selected").data("type");
                // object to be sent to server
                var dataObj = {
                    "account": selected_account,
                    "type": selected_type,
                    "body": []
                };
                $.each(url_body, function(index, body) {
                    dataObj.body[index] = {
                        'time': $(this).find('.time_dropdown').val(),
                        'feed_url': $(this).find('input[name="feed_url[]"]').val(),
                    };
                });
                var token = $('meta[name="csrf-token"]').attr('content');
                submit_button.attr('disabled', true);
                toastr.warning("Your feed posts are being fetched. Please wait...");
                $.ajax({
                    url: "{{ route('panel.automation.feedUrl') }}",
                    type: "POST",
                    data: {
                        "feedBody": dataObj,
                        "_token": token,
                    },
                    success: function(response) {
                        submit_button.attr('disabled', false);
                        if (response.success) {
                            $("#fetchPostsModal").modal("toggle");
                            reloadPosts();
                            $("#fetchPostForm").trigger("reset");
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                });

            }
            // add new url input
            $(document).on('click', '#addNewUrl', function() {
                var timeslots = @json($timeslots);
                var new_url = $('.new_url_section');
                new_url.show();
                var new_url_body = '';
                new_url_body += '<div class="col-md-8 row url_body">';
                new_url_body += '<div class="col-md-6 form-group mb-0">';
                new_url_body += '<label for="time">Time <span class="text-danger">*</span></label>';
                new_url_body +=
                    '<select name="time[]" class="form-control select2 time_dropdown" multiple required>';
                $.each(timeslots, function(index, timeslot) {
                    new_url_body += '<option value="' + timeslot + '">' + timeslot + '</option>';
                });
                new_url_body += '</select>';
                new_url_body += '</div>';
                new_url_body += '<div class="col-md-6 form-group mb-0">';
                new_url_body += '<label for="feed_url">Feed Url <span class="text-danger">*</span></label>';
                new_url_body += '<div class="row col-md-12 d-flex justify-content-between form-group">';
                new_url_body +=
                    '<input type="text" name="feed_url[]" class="col-md-10 form-control mb-2" required>';
                new_url_body += '<div class="row">'
                new_url_body +=
                    '<button type="button" class="btn btn-outline-success btn-sm ml-2 fetch_url_btn">';
                new_url_body += '<i class="fas fa-download"></i>';
                new_url_body += '</button>';
                new_url_body +=
                    '<button type="button" class="btn btn-outline-danger btn-sm ml-2 new_url_delete_btn"';
                new_url_body += 'title="Delete Selected Domains">';
                new_url_body += '<i class="fas fa-trash"></i>';
                new_url_body += '</button>';
                new_url_body += '</div>';
                new_url_body += '</div>';
                new_url_body += '</div>';
                new_url.append(new_url_body);
                initializeDynamicSelect2();
            })
            // Initilize Select2s
            function initializeDynamicSelect2() {
                $('.time_dropdown').each(function() {
                    if (!$(this).hasClass("select2-hidden-accessible")) {
                        $(this).select2();
                    }
                });
            }
            // delete new url input
            $(document).on("click", ".new_url_delete_btn", function() {
                var delete_button = $(this);
                var new_url = delete_button.closest(".url_body");
                new_url.remove();
            })
            // Fetch domains
            $('#account').on('change', function() {
                var account_id = $(this).find(":selected").val();
                var selected_type = $(this).find(":selected").data("type");
                var shuffle = $(this).find(":selected").data('shuffle');
                var rss_paused = $(this).find(":selected").data('rss-paused');
                // toggle check shuiffle checkbox
                if (shuffle == 1) {
                    $('.shuffle').attr('checked', true)
                } else {
                    $('.shuffle').attr('checked', false)
                }
                // RSS automation: checked means active (not paused), unchecked means paused
                if (rss_paused == 1) {
                    $('.rss_automation').prop('checked', false);
                    $('.rss_toggle').removeClass('active');
                } else {
                    $('.rss_automation').prop('checked', true);
                    $('.rss_toggle').addClass('active');
                }
                // hide/show shuffle button
                toggleShuffle(account_id);
                // hide/show rss button
                toggleRssToggle(account_id);
                // hide/show delete all button
                toggleDelete(account_id);
                if (account_id != '') {
                    fetchDomains(account_id, selected_type);
                }
            });
            // Fetch domains
            $('#fetch_account').on('change', function() {
                var account_id = $(this).find(":selected").val();
                var selected_type = $(this).find(":selected").data("type");
                var select = $('#feed_url');
                select.empty();
                if (account_id != '') {
                    fetchDomains(account_id, selected_type);
                }
            });
            // Fetch domains function
            var fetchDomains = function(account_id, selected_type) {
                $.ajax({
                    url: "{{ route('panel.automation.getDomain') }}",
                    method: "GET",
                    data: {
                        "account_id": account_id,
                        "type": selected_type
                    },
                    success: function(response) {
                        if (response.success) {
                            feed_urls = response.data;
                            if (feed_urls.length > 0) {
                                setFeedUrls(feed_urls);
                            } else {
                                setNewFeedUrl();
                            }
                        }
                    }
                });
            }
            // set Feed Urls
            function setFeedUrls(feed_urls) {
                var url_body = $('.url_body');
                url_body.empty();
                var new_url_section = $('.new_url_section');
                new_url_section.empty();
                var timeslots = @json($timeslots);
                console.log(feed_urls);
                $.each(feed_urls, function(index, feed_url) {
                    var url_body = $('.url_body');
                    var selectedUrl = feed_url.name;
                    var selectedTimeslots = feed_url.time;
                    var new_url_body = '';
                    new_url_body += '<div class="col-md-6 form-group mb-0">';
                    new_url_body += '<label for="time">Time <span class="text-danger">*</span></label>';
                    // time dropdown
                    time_dropdown =
                        '<select name="time[]" class="form-control select2 time_dropdown" multiple required>';
                    $.each(timeslots, function(index, timeslot) {
                        time_dropdown += '<option value="' + timeslot + '"';
                        if (selectedTimeslots.includes(timeslot)) {
                            time_dropdown += ' selected';
                        }
                        time_dropdown += '>';
                        time_dropdown += timeslot;
                        time_dropdown += '</option>';
                    });
                    time_dropdown += '</select>';
                    // time dropdown
                    new_url_body += time_dropdown;
                    new_url_body += '</div>';
                    new_url_body += '<div class="col-md-6 form-group mb-0">';
                    new_url_body +=
                        '<label for="feed_url">Feed Url <span class="text-danger">*</span></label>';
                    new_url_body += '<div class="row col-md-12 d-flex justify-content-between form-group">';
                    new_url_body +=
                        '<input type="text" name="feed_url[]" class="col-md-10 form-control mb-2" value="' +
                        selectedUrl + '" required>';
                    new_url_body += '<div class="row">';
                    new_url_body +=
                        '<button type="button" class="btn btn-outline-success btn-sm ml-2 fetch_url_btn">';
                    new_url_body += '<i class="fas fa-download"></i>';
                    new_url_body += '</button>';
                    new_url_body +=
                        '<button type="button" class="btn btn-outline-danger btn-sm ml-2 delete_domain_btn"';
                    new_url_body += 'data-url-id="' + feed_url.id + '"';
                    new_url_body += 'title="Delete Selected Domains">';
                    new_url_body += '<i class="fas fa-trash"></i>';
                    new_url_body += '</button>';
                    new_url_body += '</div>';
                    new_url_body += '</div>';

                    url_body.append(new_url_body);
                    initializeDynamicSelect2();
                });
            }
            // set New Feed Url
            function setNewFeedUrl() {
                var url_body = $('.url_body');
                url_body.empty();
                var new_url_section = $('.new_url_section');
                new_url_section.empty();
                var timeslots = @json($timeslots);
                var new_url_body = '';
                new_url_body += '<div class="col-md-6 form-group mb-0">';
                new_url_body += '<label for="time">Time <span class="text-danger">*</span></label>';
                // time dropdown
                time_dropdown =
                    '<select name="time[]" class="form-control select2 time_dropdown" multiple required>';
                $.each(timeslots, function(index, timeslot) {
                    time_dropdown += '<option value="{{ $timeslot }}">';
                    time_dropdown += timeslot;
                    time_dropdown += '</option>';
                });
                time_dropdown += '</select>';
                // time dropdown
                new_url_body += time_dropdown;
                new_url_body += '</div>';
                new_url_body += '<div class="col-md-6 form-group mb-0">';
                new_url_body += '<label for="feed_url">Feed Url <span class="text-danger">*</span></label>';
                new_url_body += '<div class="row col-md-12 d-flex justify-content-between form-group">';
                new_url_body +=
                    '<input type="text" name="feed_url[]" class="col-md-10 form-control mb-2" required>';
                new_url_body += '<div class="row">';
                new_url_body += '<button type="button" class="btn btn-outline-success btn-sm ml-2 fetch_url_btn">';
                new_url_body += '<i class="fas fa-download"></i>';
                new_url_body += '</button>';
                new_url_body += '</div>';
                new_url_body += '</div>';
                new_url_body += '</div>';
                url_body.append(new_url_body);
                initializeDynamicSelect2();
            }
            // Filters and Reset
            $(document).on('change', '.adv_filter', function() {
                save_filters();
                loadPosts(1);
            });

            // Delete Selected Domains
            $(document).on('click', '.delete_domain_btn', function() {
                var url_body = $(this).closest('.url_body');
                var feed_url = url_body.find('input[name="feed_url[]"]').val();
                var feed_url_id = $(this).data('url-id');

                if (empty(feed_url)) {
                    toastr.warning("Feed URL is empty!");
                    return;
                }

                var confirmMessage =
                    "Are you sure you want to delete this domain and all its associated posts?"
                if (confirm(confirmMessage)) {
                    var token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('panel.automation.deleteDomain') }}",
                        method: "POST",
                        data: {
                            "domain_id": feed_url_id,
                            "_token": token
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // Refresh domains dropdown
                                var account_id = $("#account").find(":selected").val();
                                var selected_type = $("#account").find(":selected").data(
                                    "type");
                                var select = $('#domains');
                                select.empty();
                                if (account_id != '') {
                                    fetchDomains(account_id, selected_type, select, 'id');
                                }
                                // Hide delete button
                                $(".delete_domain_btn").hide();
                                // Clear saved domains for this selection
                                $('#saved_domains').val('');
                                // Reload posts
                                loadPosts(currentPage);
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function() {
                            toastr.error("An error occurred while deleting domains.");
                        }
                    });
                }
            });
            $("#clearFilters").on("click", function() {
                $("#adv_filter_form").trigger("reset");
                $("#account").trigger("change");
                $(".delete_domain_btn").hide();
                reloadPosts();
            })
            // reload posts
            var drawDataTable = function() {
                reloadPosts();
            }
            // Delete Post
            $(document).on("click", ".delete_btn", function() {
                if (confirm("Are you sure you want to delete this post?")) {
                    var id = $(this).data('id');
                    var token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('panel.automation.posts.destroy') }}",
                        type: "POST",
                        data: {
                            "id": id,
                            "_token": token
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                reloadPosts();
                            } else {
                                toastr.error(response.message);
                            }
                        }
                    });
                }
            })
            // Edit Post
            $(document).on('click', '.edit_btn', function() {
                var id = $(this).data('id');
                var modal = $('#editPostModal');
                modal.find(".modal-body").empty();
                modal.modal("toggle");
                $.ajax({
                    url: "{{ route('panel.schedule.post.edit') }}",
                    type: "GET",
                    data: {
                        id: id,
                    },
                    success: function(response) {
                        if (response.success) {
                            modal.find("#editPostForm").attr("action", response.action);
                            modal.find(".modal-body").html(response.data);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                })
            })
            // image preview in edit form
            $(document).on('change', '#edit_post_publish_image', function() {
                const files = event.target.files;
                if (files.length > 0) {
                    const file = files[0];
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const dataURL = e.target.result;
                            $('#edit_post_image_preview')
                                .attr('src', dataURL)
                                .show();
                        };
                        reader.readAsDataURL(file);
                    } else {
                        alert("Please select a valid image file.");
                    }
                }
            });
            // update post
            $(document).on('submit', '#editPostForm', function(e) {
                event.preventDefault();
                var modal = $('#editPostModal');
                var date = modal.find('#edit_post_publish_date').val();
                var time = modal.find('#edit_post_publish_time').val();
                if (!checkPastDateTime(date, time)) {
                    var url = $(this).attr("action");
                    var formData = new FormData(this);
                    formData.append("_token", "{{ csrf_token() }}");
                    $.ajax({
                        url: url,
                        type: "POST",
                        processData: false,
                        contentType: false,
                        data: formData,
                        success: function(response) {
                            if (response.success) {
                                modal.modal("hide");
                                drawDataTable();
                                toastr.success(response.message);
                            } else {
                                toastr.error(response.message);
                            }
                        }
                    });
                }
            });
            // Publish Post
            $(document).on("click", ".publish-post", function() {
                if (confirm("Do you wish to Publish?")) {
                    var id = $(this).data('id');
                    var type = $(this).data("type");
                    var token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('panel.automation.posts.publish') }}/" + id,
                        method: "POST",
                        data: {
                            "_token": token,
                            "type": type
                        },
                        success: function(response) {
                            if (response.success) {
                                reloadPosts();
                                toastr.success(response.message);
                            } else {
                                toastr.error(response.message);
                            }
                        }
                    });
                }
            })
            // Toggle shuffle
            var toggleShuffle = function(id) {
                if (id != '') {
                    $(".shuffle_toggle").show();
                } else {
                    $(".shuffle_toggle").hide();
                }
            }
            // Toggle RSS toggle
            var toggleRssToggle = function(id) {
                if (id != '') {
                    $(".rss_toggle").show();
                } else {
                    $(".rss_toggle").hide();
                }
            }
            var toggleDelete = function(id) {
                if (id != '') {
                    $("#deleteAll").show();
                } else {
                    $("#deleteAll").hide();
                }
            }
            // Shuffle toggle
            $(document).on('click', '.shuffle', function() {
                var toggle = $(".shuffle");
                var shuffle = toggle.is(":checked") ? 1 : 0;
                var selected_account = $("#account").find(":selected").val();
                var selected_type = $("#account").find(":selected").data("type");
                var token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('panel.automation.posts.shuffle') }}",
                    method: "POST",
                    data: {
                        "shuffle": shuffle,
                        "account": selected_account,
                        "type": selected_type,
                        "_token": token
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                });
            })
            // RSS Automation toggle
            $(document).on('click', '.rss_automation', function() {
                var toggle = $(".rss_automation");
                var rssToggleDiv = $(".rss_toggle");
                // When checked = active (not paused), when unchecked = paused
                var isChecked = toggle.is(":checked");
                var selected_account = $("#account").find(":selected").val();
                var selected_type = $("#account").find(":selected").data("type");
                var token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('panel.accounts.toggleRssPause') }}",
                    method: "POST",
                    data: {
                        "id": selected_account,
                        "type": selected_type,
                        "_token": token
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Update the data attribute on the option
                            $("#account").find(":selected").data('rss-paused', response.paused ?
                                1 : 0);
                            // Update the visual state
                            if (response.paused) {
                                rssToggleDiv.removeClass('active');
                            } else {
                                rssToggleDiv.addClass('active');
                            }
                        } else {
                            toastr.error(response.message);
                            // Revert the checkbox state
                            toggle.prop('checked', !isChecked);
                        }
                    },
                    error: function() {
                        toastr.error('Something went wrong!');
                        // Revert the checkbox state
                        toggle.prop('checked', !isChecked);
                    }
                });
            })
            // Delete All
            $(document).on('click', '#deleteAll', function() {
                if (confirm("Do you wish to Delete all Posts!")) {
                    var selected_account = $("#account").find(":selected").val();
                    var selected_type = $("#account").find(":selected").data("type");
                    var domain = $("#domains").val();
                    var token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('panel.automation.posts.deleteAll') }}",
                        method: "POST",
                        data: {
                            "account": selected_account,
                            "type": selected_type,
                            "domain": domain,
                            "_token": token
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                reloadPosts();
                            } else {
                                toastr.error(response.message);
                            }
                        }
                    });
                }
            })
            // Fix post image/title
            $(document).on('click', '.fix-post', function() {
                var id = $(this).data('post-id');
                var token = $('meta[name="csrf-token"]').attr('content');
                if (confirm("Do you want to Fix this post?")) {
                    $.ajax({
                        url: "{{ route('panel.automation.posts.fix') }}",
                        method: "POST",
                        data: {
                            "id": id,
                            "_token": token
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                reloadPosts();
                            } else {
                                toastr.error(response.message);
                            }
                        }
                    });
                }
            });
            // Save filters
            var save_filters = function() {
                var token = $('meta[name="csrf-token"]').attr('content');
                var selected_account = $("#account").find(":selected").val();
                var selected_type = $("#account").find(":selected").data("type");
                if (selected_account && selected_type) {
                    $.ajax({
                        url: "{{ route('panel.automation.saveFilters') }}",
                        method: "POST",
                        data: {
                            "_token": token,
                            "selected_account": selected_account,
                            "selected_type": selected_type,
                        }
                    });
                }
            }

            // Trigger change event if account is pre-selected from saved filters
            var preSelectedAccount = $("#account").val();
            if (preSelectedAccount && preSelectedAccount !== '') {
                $("#account").trigger('change');
            }
        });
    </script>
    <script>
        var imageInput = document.getElementById('post_image');
        var imagePreview = document.getElementById('post_image_preview');
        if (imageInput) {
            imageInput.addEventListener('change', function(event) {
                var file = event.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.src = "/img/noimage.png";
                }
            });
        }

        // Image Lightbox functionality for posts grid
        $(document).on('click',
            '.automation-post-card .pinterest_card .image-container img.post-image, .automation-post-card .facebook_card .pronunciation-image-container img',
            function(e) {
                e.preventDefault();
                e.stopPropagation();

                var imgSrc = $(this).attr('src');
                var imgAlt = $(this).attr('alt') || '';

                // Get post title from the card
                var $card = $(this).closest('.pinterest_card, .facebook_card');
                var caption = $card.find('.card-content span:last, .mb-3.px-3 span').first().text().trim();

                $('#lightboxImage').attr('src', imgSrc);
                $('#lightboxCaption').text(caption || imgAlt);
                $('#imageLightbox').addClass('active');

                // Prevent body scroll
                $('body').css('overflow', 'hidden');
            });

        // Close lightbox on close button click
        $('#lightboxClose').on('click', function() {
            closeLightbox();
        });

        // Close lightbox on backdrop click
        $('.lightbox-backdrop').on('click', function() {
            closeLightbox();
        });

        // Close lightbox on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#imageLightbox').hasClass('active')) {
                closeLightbox();
            }
        });

        function closeLightbox() {
            $('#imageLightbox').removeClass('active');
            $('body').css('overflow', '');
        }
    </script>
@endpush
