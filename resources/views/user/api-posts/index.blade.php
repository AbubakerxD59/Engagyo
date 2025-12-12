@extends('user.layout.main')
@section('title', 'API Posts')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <span><i class="fas fa-code mr-2"></i>API Posts</span>
                        </div>
                        <div class="card-tools">
                            <a href="{{ route('api.docs') }}" target="_blank" class="btn btn-sm btn-outline-info mr-2">
                                <i class="fas fa-book mr-1"></i> API Docs
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            <i class="fas fa-info-circle mr-1"></i>
                            These are posts created via the API. You can view, edit, or delete them here.
                        </p>

                        {{-- Filters --}}
                        <div class="row m-0 p-0 mb-4">
                            <div class="col-md-3">
                                <label for="account">Account</label>
                                <select name="account" id="account" class="form-control select2 filter" multiple>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">{{ ucfirst($account->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="type">Social type</label>
                                <select name="type" id="type" class="form-control select2 filter" multiple>
                                    @foreach (get_options('social_accounts') as $social_account)
                                        <option value="{{ $social_account }}">{{ ucfirst($social_account) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="post_type">Post Type</label>
                                <select name="post_type" id="post_type" class="form-control select2 filter" multiple>
                                    <option value="photo">Image</option>
                                    <option value="link">Link</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control select2 filter" multiple>
                                    <option value="0">Pending</option>
                                    <option value="1">Published</option>
                                    <option value="-1">Failed</option>
                                </select>
                            </div>
                        </div>

                        {{-- Posts Grid --}}
                        <div id="postsGrid" class="api-posts-grid">
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
    @include('user.api-posts.modals.edit-post-modal')

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
    @include('user.schedule.assets.style')
    @include('user.schedule.assets.facebook_post')
    @include('user.schedule.assets.pinterest_post')
    <style>
        /* API Posts Grid Layout */
        .api-posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .api-posts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .api-posts-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Post Card Container */
        .api-post-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 580px;
        }

        .api-post-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        /* Post Preview Section */
        .api-post-card .post-preview {
            height: 320px;
            overflow: hidden;
            position: relative;
        }

        .api-post-card .post-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.9));
            pointer-events: none;
        }

        .api-post-card .post-preview .pinterest_card,
        .api-post-card .post-preview .facebook_card {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
            height: 100%;
            overflow: hidden;
        }

        .api-post-card .post-preview .pinterest_card .image-container,
        .api-post-card .post-preview .facebook_card .pronunciation-image-container {
            max-height: 180px;
            overflow: hidden;
        }

        .api-post-card .post-preview .pinterest_card .image-container img,
        .api-post-card .post-preview .facebook_card .pronunciation-image-container img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        /* Post Meta Section */
        .api-post-card .post-meta {
            padding: 12px 15px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow-y: auto;
        }

        .api-post-card .post-meta::-webkit-scrollbar {
            width: 4px;
        }

        .api-post-card .post-meta::-webkit-scrollbar-thumb {
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
        .account-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e9ecef;
        }

        .account-badge img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }

        .account-badge .platform-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #fff;
        }

        .account-badge .platform-icon.facebook {
            background: #1877F2;
        }

        .account-badge .platform-icon.pinterest {
            background: #E60023;
        }

        .account-badge .platform-icon.tiktok {
            background: #000000;
        }

        .account-badge .account-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* API Key Badge */
        .api-key-badge {
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

        .api-key-badge i {
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
        .api-post-card .post-preview img.post-image,
        .api-post-card .post-preview .pronunciation-image-container img {
            cursor: zoom-in;
            transition: opacity 0.2s ease;
        }

        .api-post-card .post-preview img.post-image:hover,
        .api-post-card .post-preview .pronunciation-image-container img:hover {
            opacity: 0.9;
        }

        /* Magnify icon on hover */
        .api-post-card .post-preview .image-container,
        .api-post-card .post-preview .pronunciation-image-container {
            position: relative;
        }

        .api-post-card .post-preview .image-container::before,
        .api-post-card .post-preview .pronunciation-image-container::before {
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

        .api-post-card .post-preview .image-container:hover::before,
        .api-post-card .post-preview .pronunciation-image-container:hover::before {
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
        $(document).ready(function() {
            // Initialize Select2
            $(".select2").select2({
                placeholder: "Select",
                allowClear: true,
            });

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
                    url: "{{ route('panel.api-posts.posts.listing') }}",
                    type: "GET",
                    data: {
                        draw: 1,
                        start: (page - 1) * perPage,
                        length: perPage,
                        account_id: $("#account").val(),
                        type: $("#type").val(),
                        post_type: $("#post_type").val(),
                        status: $("#status").val(),
                    },
                    success: function(response) {
                        totalPosts = response.iTotalDisplayRecords;
                        renderPosts(response.data);
                        renderPagination();
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
                            <p>No API posts found</p>
                            <small>Posts created via API will appear here</small>
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
                var platformIcon = post.social_type === 'facebook' ? 'fab fa-facebook-f' : (post.social_type ===
                    'pinterest' ? 'fab fa-pinterest-p' : 'fab fa-tiktok');
                var platformClass = post.social_type;

                var apiKeyBadge = post.api_key_name && post.api_key_name !== '-' ?
                    `<span class="api-key-badge"><i class="fas fa-key"></i> ${post.api_key_name}</span>` :
                    '';

                var publishedAt = post.status == 1 && post.published_at_formatted ?
                    `<div class="published-at">Published at: ${post.published_at_formatted}</div>` :
                    '';

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
                        <button class="btn btn-outline-success btn-sm publish_now_btn" data-id="${post.id}" title="Publish Now">
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
                    <div class="api-post-card">
                        <div class="post-preview">
                            ${post.post_details}
                        </div>
                        <div class="post-meta">
                            <div class="post-meta-row">
                                <div class="account-badge">
                                    <span class="platform-icon ${platformClass}">
                                        <i class="${platformIcon}"></i>
                                    </span>
                                    <span class="account-name">${post.account_name || 'Unknown'}</span>
                                </div>
                                ${apiKeyBadge}
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
                    paginationHtml +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
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
                if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass(
                        'active')) {
                    loadPosts(page);
                }
            });

            // Filter change
            $(".filter").on("change", function() {
                loadPosts(1);
            });

            // Delete post
            $(document).on("click", ".delete_btn", function() {
                let id = $(this).data("id");
                if (confirm("Are you sure you want to delete this post?")) {
                    $.ajax({
                        url: "{{ route('panel.api-posts.post.delete') }}",
                        type: "GET",
                        data: {
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                loadPosts(currentPage);
                            } else {
                                toastr.error(response.message);
                            }
                        },
                    });
                }
            });

            // Edit post
            $(document).on("click", ".edit_btn", function() {
                let id = $(this).data("id");
                $.ajax({
                    url: "{{ route('panel.api-posts.post.edit') }}",
                    type: "GET",
                    data: {
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            $("#edit_post_body").html(response.data);
                            $("#edit_post_form").attr("action", response.action);
                            $("#editPostModal").modal("show");
                        } else {
                            toastr.error(response.message);
                        }
                    },
                });
            });

            // Update post form submit
            $(document).on("submit", "#edit_post_form", function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: $(this).attr("action"),
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $("#editPostModal").modal("hide");
                            loadPosts(currentPage);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                });
            });

            // Publish now
            $(document).on("click", ".publish_now_btn", function() {
                let id = $(this).data("id");
                if (confirm("Are you sure you want to publish this post now?")) {
                    $.ajax({
                        url: "{{ route('panel.api-posts.post.publish.now') }}",
                        type: "POST",
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}",
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                loadPosts(currentPage);
                            } else {
                                toastr.error(response.message);
                            }
                        },
                    });
                }
            });

            // Initial load
            loadPosts(1);

            // Image Lightbox functionality
            $(document).on('click',
                '.api-post-card .post-preview img.post-image, .api-post-card .post-preview .pronunciation-image-container img',
                function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var imgSrc = $(this).attr('src');
                    var imgAlt = $(this).attr('alt') || '';

                    // Get post title from the card
                    var $card = $(this).closest('.api-post-card');
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
        });
    </script>
@endpush
