<script>
    $(document).ready(function() {
        // global variables
        var action_name = '';
        var current_file = 0;
        var is_link = 0;
        var is_video = 0;
        // character count
        getCharacterCount($('.check_count'));
        // account status
        $(".account-card").on("click", function() {
            var $card = $(this);
            $card.toggleClass("active");
            var type = $card.data("type");
            var id = $card.data("id");
            var status = $card.hasClass("active") ? 1 : 0;
            $.ajax({
                url: "{{ route('panel.schedule.account.status') }}",
                type: "GET",
                data: {
                    "type": type,
                    "id": id,
                    "status": status,
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        $card.toggleClass("active");
                        toastr.error(response.message);
                    }
                },
                error: function(response) {
                    $card.toggleClass("active");
                    toastr.error("Something went Wrong!");
                }
            });
        });
        // DropZone
        var dropZone = new Dropzone("#dropZone", {
            autoProcessQueue: false,
            url: '{{ route('panel.schedule.process.post') }}',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            maxFiles: 10,
            paramName: "files",
            maxFilesize: 256,
            acceptedFiles: 'image/*, video/*',
            parallelUploads: 100,
            addRemoveLinks: true,
            dictRemoveFile: "×",
            dictCancelUpload: "X",
            init: function() {
                // file added
                this.on("addedfile", function(file) {
                    var supportedFormats = [
                        'image/jpeg', 'image/jpg',
                        'image/png',
                        'video/mp4',
                        'video/quicktime',
                        'video/mpg',
                        'video/webm',
                        'video/mov'
                    ];
                    var fileExtension = file.name.split('.').pop().toLowerCase();
                    if (!$.inArray(fileExtension, supportedFormats) ||
                        (fileExtension !== 'jpg' &&
                            fileExtension !== 'jpeg' &&
                            fileExtension !== 'png' &&
                            fileExtension !== 'bmp' &&
                            fileExtension !== 'gif' &&
                            fileExtension !== 'tiff' &&
                            fileExtension !== 'webp' &&
                            fileExtension !== 'mp4' &&
                            fileExtension !== 'mkv' &&
                            fileExtension !== 'mov' &&
                            fileExtension !== 'mpeg' &&
                            fileExtension !== 'webm'
                        )
                    ) {
                        toastr.error("This image format is not supported.");
                        this.removeFile(file);
                    }
                    if (fileExtension == 'mp4' ||
                        fileExtension == 'mkv' ||
                        fileExtension == 'mov' ||
                        fileExtension == 'mpeg' ||
                        fileExtension == 'webm') {
                        is_video = 1;
                    }
                });
                // file sending
                this.on("sending", function(file, xhr, data) {
                    var content = $("#content").val();
                    var comment = $("#comment").val();
                    var schedule_date = $("#schedule_date").val();
                    var schedule_time = $("#schedule_time").val();
                    var action = action_name;

                    data.append("content", content);
                    data.append("comment", comment);
                    data.append("link", is_link);
                    data.append("video", is_video);
                    // for schedule action
                    data.append("schedule_date", schedule_date);
                    data.append("schedule_time", schedule_time);
                    data.append("action", action);
                });
                // request success
                this.on("success", function(file, response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                    this.removeFile(file);
                    processQueueWithDelay(dropZone.files);
                });
                // request complete
                this.on("complete", function(file) {
                    if (this.getUploadingFiles().length === 0 &&
                        this.getQueuedFiles().length === 0) {
                        resetPostArea();
                    }
                });
                // request error
                this.on("error", function(file, response) {
                    toastr.error(response.message);
                });
            }
        });
        // publish/queue/schedule post
        $('.action_btn').on('click', function() {
            action_name = $(this).attr("href");
            // for schedule
            if (action_name == "schedule") {
                var schedule_modal = $(".schedule-modal");
                schedule_modal.modal("toggle");
            } else {
                // for link posting
                if (is_link) {
                    if (checkAccounts()) {
                        processLink();
                        return true;
                    } else {
                        toastr.error("Please select atleast one channel!");
                    }
                } else {
                    // schedule posting
                    validateAndProcess();
                }
            }
        });
        $(document).on('click', '.schedule_btn', function() {
            var schedule_date = $('#schedule_date').val();
            var schedule_time = $('#schedule_time').val();
            if (empty(schedule_date) || empty(schedule_time)) {
                toastr.error("Schedule date & time are required!");
                return false;
            }
            if (!checkPastDateTime(schedule_date, schedule_time)) {
                if (is_link) {
                    processLink();
                } else {
                    validateAndProcess();
                }
            }
        });
        // validate and process post
        var validateAndProcess = function() {
            var isValid = false;
            // check accounts
            if (checkAccounts()) {
                isValid = true;
            }
            if (isValid) {
                // check content
                var drop_files = dropZone.getAcceptedFiles().length;
                if (drop_files == 0) {
                    var content = $("#content").val();
                    if (empty(content)) {
                        isValid = false;
                    }
                    if (isValid) {
                        processContentOnly();
                    } else {
                        toastr.error("Please provide Post title!");
                    }
                } else {
                    if (isValid) {
                        var filesCopy = [...dropZone.files];
                        processQueueWithDelay(filesCopy);
                    } else {
                        toastr.error("Please provide Post title!");
                    }
                }
            } else {
                toastr.error("Please select atleast one channel!");
            }
        }
        // check accounts status
        var checkAccounts = function() {
            var account = false;
            $('.account-card').each(function() {
                if ($(this).hasClass("active")) {
                    account = true;
                }
            });
            return account;
        }
        // process dropzone queue
        function processQueueWithDelay(filesCopy) {
            disableActionButton();
            if (filesCopy.length > current_file) {
                var file = filesCopy[current_file];
                dropZone.processFile(file);
            } else {
                // All files processed
                current_file = 0;
                resetPostArea();
            }
        }
        // process content only
        var processContentOnly = function() {
            disableActionButton();
            var content = $('#content').val();
            var comment = $('#comment').val();
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "content": content,
                    "comment": comment,
                    "link": 0,
                    "action": action_name
                },
                success: function(response) {
                    if (response.success) {
                        resetPostArea();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                    enableActionButton();
                }
            })
        }
        // process link post
        var processLink = function() {
            var content = $('#content').val();
            var comment = $('#comment').val();
            var image = $('#link_image').attr('src');
            var url = $('#article-container .link_url').text();
            var schedule_date = $("#schedule_date").val();
            var schedule_time = $("#schedule_time").val();
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "content": content,
                    "comment": comment,
                    "link": 1,
                    "url": url,
                    "image": image,
                    "schedule_date": schedule_date,
                    "schedule_time": schedule_time,
                    "action": action_name,
                },
                success: function(response) {
                    if (response.success) {
                        resetPostArea();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
        // reset post area
        var resetPostArea = function() {
            dropZone.removeAllFiles(true);
            current_file = 0;
            is_link = 0;
            is_video = 0;
            $('#content').val('');
            $('#comment').val('');
            $('#characterCount').text('');
            $('#article-container').empty();
            reloadPosts();
            enableActionButton();
        }
        // check link for content
        $('#content').on('input', function() {
            var value = $(this).val();
            is_link = 0;
            if (checkLink(value)) {
                var link_data = fetchFromLink(value);
            }
        });
        // fetch from link
        var fetchFromLink = function(link) {
            if (link) {
                // render skeleton
                renderSkeletonLoader();
                disableActionButton();
                $.ajax({
                    url: "{{ route('general.previewLink') }}",
                    type: "GET",
                    data: {
                        "link": link,
                    },
                    success: function(response) {
                        if (response.success) {
                            var title = response.title;
                            var image = response.image;
                            if (!empty(title)) {
                                $("#content").val(response.title);
                                $("#content").trigger("input");
                            }
                            if (!empty(image)) {
                                renderArticleContent(response);
                                is_link = 1;
                            } else {
                                container.html(
                                    '<div style="padding: 1rem; color: #DC2626;">Error loading data. Please try again.</div>'
                                );
                            }
                        } else {
                            container.html(
                                '<div style="padding: 1rem; color: #DC2626;">Error loading data. Please try again.</div>'
                            );
                            toastr.error(response.message);
                        }
                        setTimeout(function() {
                            enableActionButton();
                        }, 500);
                    }
                });
            }
        };
        // disable action buttons
        var disableActionButton = function() {
            $('.action_btn').attr("disabled", true);
            $('.schedule_btn').attr("disabled", true);
        };
        // enable action buttons
        var enableActionButton = function() {
            $('.action_btn').attr("disabled", false);
            $('.schedule_btn').attr("disabled", false);
            $('.schedule-modal').modal("hide");
        };
        // settings modal
        $('.setting_btn').on("click", function() {
            var modal = $('.settings-modal');
            modal.find(".modal-body").empty();
            modal.modal("toggle");
            // $.ajax({
            //     url: "{{ route('panel.schedule.get.setting') }}",
            //     type: "GET",
            //     success: function(response) {
            //         if (response.success) {
            //             modal.find(".modal-body").html(response.data);
            //             // select2
            //             $('.select2').select2({
            //                 closeOnSelect: false
            //             });
            //         } else {
            //             toastr.error("Something went Wrong!");
            //         }
            //     }
            // });
        });
        // update timeslots
        $(document).on("change", ".timeslot", function() {
            var id = $(this).data("id");
            var type = $(this).data("type");
            var timeslots = $(this).val();
            $.ajax({
                url: "{{ route('panel.schedule.timeslot.setting') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": id,
                    "type": type,
                    "timeslots": timeslots,
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        });
        // link loading and preview
        const container = $('#article-container');

        function renderSkeletonLoader() {
            const skeletonHTML = `
                    <div id="skeleton-loader" class="skeleton-wrapper">     
                        <!-- Left Column (Text Content) -->
                        <div class="content-col">
                            <!-- Title Line -->
                            <div class="skeleton-bar bar-title animate-pulse-slow"></div>

                            <!-- Body Line 1 (Longest) -->
                            <div class="skeleton-bar bar-full animate-pulse-slow"></div>
                            
                            <!-- Body Line 2 (Medium) -->
                            <div class="skeleton-bar bar-medium animate-pulse-slow"></div>

                            <!-- Body Line 3 (Shortest, like a secondary detail) -->
                            <div class="skeleton-bar bar-short animate-pulse-slow" style="margin-bottom: 0;"></div>
                        </div>
                        <!-- Right Column (Image/Sidebar Block) -->
                        <div class="image-col">
                            <!-- Image block placeholder -->
                            <div class="skeleton-bar image-placeholder animate-pulse-slow"></div>
                            
                            <!-- Close Button placeholder -->
                            <button class="close-btn-placeholder" disabled>
                                X
                            </button>
                        </div>
                    </div>
                `;
            container.html(skeletonHTML);
        }

        function renderArticleContent(data) {
            const articleHTML = `
                    <div id="real-article" class="real-article-wrapper">  
                        <!-- Left Column (Text Content) -->
                        <div class="content-col">
                            <h5 class="link_title" title="${data.title}">${data.title.substring(0, 60)}...</h5>
                            <p class="link_url">${data.link}</p>
                        </div>
                        <!-- Right Column (Image/Sidebar) -->
                        <div class="image-col" style="margin-left: 1rem;">
                            <img id="link_image" src="${data.image}" alt="Feature Icon">
                            <!-- Close Button (Functional) -->
                            <button class="close-btn-placeholder"
                                style="background-color: black; color: white; cursor: pointer;">
                                X
                            </button>
                        </div>
                    </div>`;
            container.html(articleHTML);
            $('#real-article').animate({
                opacity: 1
            }, 1000);
        }
        $(document).on('click', '.close-btn-placeholder', function() {
            resetPostArea();
        });
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
                url: "{{ route('panel.schedule.posts.listing') }}",
                type: "GET",
                data: {
                    draw: 1,
                    start: (page - 1) * perPage,
                    length: perPage,
                    account_id: $("#filter_account").val(),
                    type: $("#filter_type").val(),
                    post_type: $("#filter_post_type").val(),
                    status: $("#filter_status").val(),
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
                        <p>No posts found</p>
                        <small>Create a new post using the form above</small>
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
            var platformIcon = post.social_type === 'facebook' ? 'fab fa-facebook-f' : (post.social_type === 'pinterest' ? 'fab fa-pinterest-p' : 'fab fa-tiktok');
            var platformClass = post.social_type;

            // Source badge
            var sourceBadge = '';
            if (post.source) {
                var sourceIcon = post.source === 'rss' ? 'fa-rss' : (post.source === 'api' ? 'fa-code' :
                    'fa-edit');
                var sourceClass = post.source === 'rss' ? 'rss' : (post.source === 'api' ? 'api' : 'manual');
                sourceBadge =
                    `<span class="source-badge ${sourceClass}"><i class="fas ${sourceIcon}"></i> ${post.source.toUpperCase()}</span>`;
            }

            var publishedAt = post.status == 1 && post.published_at ?
                `<div class="published-at">Published at: ${post.published_at_formatted || post.published_at}</div>` :
                '';

            var responseHtml = '';
            if (post.response) {
                var responseClass = post.status == 1 ? 'success' : (post.status == -1 ? 'error' : '');
                var responseText = post.response.length > 100 ? post.response.substring(0, 100) + '...' : post
                    .response;
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
                <div class="schedule-post-card">
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
                            ${sourceBadge}
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
        $(document).on('change', '.filter', function() {
            loadPosts(1);
        });

        // Reload posts function (for use after actions)
        var reloadPosts = function() {
            loadPosts(currentPage);
        }

        // Initial load
        loadPosts(1)
        // delete post
        $(document).on('click', '.delete_btn', function() {
            if (confirm(
                    "Published post will be delete from Account! Do you wish to Delete this Post?")) {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('panel.schedule.post.delete') }}",
                    type: "GET",
                    data: {
                        id: id,
                    },
                    success: function(response) {
                        if (response.success) {
                            reloadPosts();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                })
            } else {
                return;
            }
        });
        // edit post
        $(document).on('click', '.edit_btn', function() {
            var id = $(this).data('id');
            var modal = $('.edit-post-modal');
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
                        modal.find("#update-post-form").attr("action", response.action);
                        modal.find(".modal-body").html(response.data);
                    } else {
                        toastr.error(response.message);
                    }
                }
            })
        });
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
        $(document).on('submit', '#update-post-form', function(e) {
            event.preventDefault();
            var modal = $('.edit-post-modal');
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
                            reloadPosts();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                });
            }
        });
        // publish now
        $(document).on('click', '.publish_now_btn', function() {
            if (confirm("Do you wish to Publish this Post Now?")) {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('panel.schedule.post.publish.now') }}",
                    type: "POST",
                    data: {
                        id: id,
                    },
                    success: function(response) {
                        if (response.success) {
                            reloadPosts();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                })
            } else {
                return;
            }
        });

        // Image Lightbox functionality for posts grid
        $(document).on('click',
            '.schedule-post-card .pinterest_card .image-container img.post-image, .schedule-post-card .facebook_card .pronunciation-image-container img',
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
    });
</script>
