<script>
    $(document).ready(function() {
        // global variables
        var action_name = '';
        var current_file = 0;
        var is_link = 0;
        var is_video = 0;
        var currentShortUrl = null;
        var originalUrlInContent = null;
        // TikTok Modal Functions
        var currentTikTokFile = null;
        var currentTikTokAccounts = [];
        // Variables for TikTok link posts
        var currentTikTokLinkUrl = null;
        var currentTikTokLinkImage = null;
        var currentTikTokScheduleDate = null;
        var currentTikTokScheduleTime = null;
        // character count
        getCharacterCount($('.check_count'));
        // Get selected accounts from account cards
        function getSelectedAccounts() {
            var accountIds = [];
            var accountTypes = [];
            $('.account-card.active').each(function() {
                var accountId = $(this).data("id");
                var accountType = $(this).data("type");
                if (accountId && accountType) {
                    accountIds.push(accountId);
                    if (accountTypes.indexOf(accountType) === -1) {
                        accountTypes.push(accountType);
                    }
                }
            });
            return {
                accountIds: accountIds,
                accountTypes: accountTypes
            };
        }

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
                        // Reload posts when account selection changes
                        loadPosts(1);
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
                    // With file in dropzone, treat any pasted link as text and post as photo/video
                    is_link = 0;
                    $('#article-container').empty();
                    toggleContentShortenerVisibility();
                });
                // file sending (with file = always photo/video post, not link)
                this.on("sending", function(file, xhr, data) {
                    var content = $("#content").val();
                    if ($('#use_short_link_content').is(':checked') &&
                        originalUrlInContent && currentShortUrl) {
                        content = content.replace(originalUrlInContent, currentShortUrl);
                    }
                    var comment = $("#comment").val();
                    var schedule_date = $("#schedule_date").val();
                    var schedule_time = $("#schedule_time").val();
                    var action = action_name;

                    data.append("content", content);
                    data.append("comment", comment);
                    data.append("link", 0);
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
                // for link posting only when no file in dropzone; with file we publish as photo/video
                if (is_link && dropZone.getAcceptedFiles().length === 0) {
                    if (checkAccounts()) {
                        processLink();
                        return true;
                    } else {
                        toastr.error("Please select atleast one channel!");
                    }
                } else {
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
                if (is_link && dropZone.getAcceptedFiles().length === 0) {
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
                // Check if TikTok accounts are selected
                var hasTikTokAccounts = false;
                var tiktokAccounts = [];
                $('.account-card.active').each(function() {
                    if ($(this).data('type') === 'tiktok') {
                        hasTikTokAccounts = true;
                        tiktokAccounts.push({
                            id: $(this).data('id'),
                            name: $(this).find('.account-name').text().trim()
                        });
                    }
                });

                // If TikTok accounts are selected and there are files, show TikTok modal
                if (hasTikTokAccounts && dropZone.getAcceptedFiles().length > 0) {
                    var filesCopy = [...dropZone.files];
                    if (filesCopy.length > 0) {
                        showTikTokModal(filesCopy[0], tiktokAccounts);
                        return;
                    }
                }

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
                        toastr.error("Please enter post content or upload a file!");
                    }
                } else {
                    if (isValid) {
                        if ($('#use_short_link_content').is(':checked') && !currentShortUrl) {
                            toastr.error('Please wait for the link to shorten.');
                            return;
                        }
                        var filesCopy = [...dropZone.files];
                        processQueueWithDelay(filesCopy);
                    } else {
                        toastr.error("Please enter post content or upload a file!");
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
            if ($('#use_short_link_content').is(':checked') && !currentShortUrl) {
                toastr.error('Please wait for the link to shorten.');
                return;
            }
            disableActionButton();
            var content = $('#content').val();
            if ($('#use_short_link_content').is(':checked') && originalUrlInContent && currentShortUrl) {
                content = content.replace(originalUrlInContent, currentShortUrl);
            }
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
            var title = $('#content').val();
            var originalUrl = $('.link_url').text().trim();
            var useShortForLink = $('#use_short_link').length && $('#use_short_link').is(':checked');
            if (useShortForLink && !currentShortUrl) {
                toastr.error('Please wait for the link to shorten.');
                return;
            }
            var url = (useShortForLink && currentShortUrl) ? currentShortUrl : originalUrl;
            var schedule_date = $("#schedule_date").val();
            var schedule_time = $("#schedule_time").val();

            // Check if TikTok accounts are selected
            var hasTikTokAccounts = false;
            var tiktokAccounts = [];
            $('.account-card.active').each(function() {
                if ($(this).data('type') === 'tiktok') {
                    hasTikTokAccounts = true;
                    tiktokAccounts.push({
                        id: $(this).data('id'),
                        name: $(this).find('.account-name').text().trim()
                    });
                }
            });

            // If TikTok accounts are selected, show TikTok modal for link posts
            if (hasTikTokAccounts && url && image) {
                showTikTokLinkModal(title, url, image, tiktokAccounts, schedule_date, schedule_time);
                return;
            }

            // For non-TikTok accounts, process normally
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
            currentShortUrl = null;
            originalUrlInContent = null;
            $('#content').val('');
            $('#comment').val('');
            $('#characterCount').text('');
            $('#article-container').empty();
            reloadPosts();
            enableActionButton();
        }
        // Extract first URL from text (for shortening when link is in content but post is photo/content)
        function extractUrlFromContent(text) {
            if (!text || !text.trim()) return null;
            var m = text.trim().match(/https?:\/\/[^\s"'<>]+/);
            return m ? m[0].replace(/[.,;:!?)]+$/, '') : null;
        }
        // Show/hide content URL shortener (when link is in textarea but post type is photo/content)
        function toggleContentShortenerVisibility() {
            var value = $("#content").val();
            var urlInContent = extractUrlFromContent(value);
            var isPhotoOrContentPost = !is_link;
            if (isPhotoOrContentPost && urlInContent) {
                $('#content-url-shortener-wrap').show();
            } else {
                $('#content-url-shortener-wrap').hide();
                if (!is_link) {
                    $('#use_short_link_content').prop('checked', false);
                    $('#short-link-result-content').hide();
                    $('#short_link_url_display_content').val('');
                    currentShortUrl = null;
                    originalUrlInContent = null;
                }
            }
        }
        // check link for content (only fetch when no file in dropzone; otherwise treat pasted URL as text)
        $('#content').on('input', function() {
            var value = $(this).val();
            is_link = 0;
            if (checkLink(value) && dropZone.getAcceptedFiles().length === 0) {
                fetchFromLink(value);
            }
            toggleContentShortenerVisibility();
        });
        // fetch from link
        var fetchFromLink = function(link) {
            if (link) {
                $('#content-url-shortener-wrap').hide();
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

            // Reset tracking when modal opens
            originalQueueTimeslots = {};
            queueTimeslotsChanged = false;
            $('#saveQueueSettings').hide();

            // Store original timeslots after modal is shown and select2 is initialized
            modal.off('shown.bs.modal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('.timeslot').each(function() {
                        var $select = $(this);
                        var accountId = $select.data("id");
                        var accountType = $select.data("type");
                        var key = accountType + '_' + accountId;
                        var originalValue = $select.val() ? $select.val().sort()
                            .join(',') : '';
                        originalQueueTimeslots[key] = originalValue;
                    });
                }, 300);
            });

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
        // Track original timeslots for queue settings modal
        var originalQueueTimeslots = {};
        var queueTimeslotsChanged = false;

        // Track timeslot changes (don't update immediately)
        $(document).on("change", ".timeslot", function() {
            var $select = $(this);
            var accountId = $select.data("id");
            var accountType = $select.data("type");
            var key = accountType + '_' + accountId;
            var currentValue = $select.val() ? $select.val().sort().join(',') : '';

            // Check if timeslots have changed
            if (originalQueueTimeslots[key] !== currentValue) {
                queueTimeslotsChanged = true;
                $('#saveQueueSettings').show();
            } else {
                // Check if all timeslots match originals
                checkQueueTimeslotChanges();
            }
        });

        // Check if queue timeslots have changed
        function checkQueueTimeslotChanges() {
            queueTimeslotsChanged = false;
            $('.timeslot').each(function() {
                var $select = $(this);
                var accountId = $select.data("id");
                var accountType = $select.data("type");
                var key = accountType + '_' + accountId;
                var currentValue = $select.val() ? $select.val().sort().join(',') : '';

                if (originalQueueTimeslots[key] !== currentValue) {
                    queueTimeslotsChanged = true;
                    return false; // break loop
                }
            });
            if (!queueTimeslotsChanged) {
                $('#saveQueueSettings').hide();
            }
        }

        // Save queue settings
        $(document).on('click', '#saveQueueSettings', function() {
            var timeslotData = [];
            $('.timeslot').each(function() {
                var $select = $(this);
                var accountId = $select.data("id");
                var accountType = $select.data("type");
                var timeslots = $select.val();

                if (timeslots && timeslots.length > 0) {
                    timeslotData.push({
                        id: accountId,
                        type: accountType,
                        timeslots: timeslots
                    });
                }
            });

            if (timeslotData.length === 0) {
                toastr.warning("Please select at least one timeslot for an account.");
                return;
            }

            var token = "{{ csrf_token() }}";
            var $saveBtn = $(this);
            $saveBtn.prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

            $.ajax({
                url: "{{ route('panel.schedule.timeslot.setting.save') }}",
                type: "POST",
                data: {
                    "_token": token,
                    "timeslot_data": timeslotData,
                },
                success: function(response) {
                    $saveBtn.prop('disabled', false).html(
                        '<i class="fas fa-save mr-1"></i> Save Changes');
                    if (response.success) {
                        toastr.success(response.message);
                        // Reset tracking
                        originalQueueTimeslots = {};
                        queueTimeslotsChanged = false;
                        $('#saveQueueSettings').hide();
                        // Update original timeslots
                        $('.timeslot').each(function() {
                            var $select = $(this);
                            var accountId = $select.data("id");
                            var accountType = $select.data("type");
                            var key = accountType + '_' + accountId;
                            var currentValue = $select.val() ? $select.val().sort()
                                .join(',') : '';
                            originalQueueTimeslots[key] = currentValue;
                        });
                        // Reload posts if needed
                        if (typeof reloadPosts === 'function') {
                            reloadPosts();
                        }
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    $saveBtn.prop('disabled', false).html(
                        '<i class="fas fa-save mr-1"></i> Save Changes');
                    toastr.error('Something went wrong!');
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
                            <div class="shortener-row mt-2">
                                <label class="d-flex align-items-center mb-1">
                                    <input type="checkbox" id="use_short_link" name="use_short_link" class="mr-2">
                                    <span>Shorten link for this post</span>
                                </label>
                                <div id="short-link-result" class="mt-1" style="display:none;">
                                    <label class="small text-muted mb-0">Shortened link:</label>
                                    <div class="input-group input-group-sm mt-1">
                                        <input type="text" id="short_link_url_display" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary copy-short-link" title="Copy">Copy</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right Column (Image/Sidebar) -->
                        <div class="image-col" style="margin-left: 1rem;">
                            <img id="link_image" src="${data.image}" alt="Feature Icon" loading="lazy">
                            <!-- Close Button (Functional) -->
                            <button class="close-btn-placeholder"
                                style="background-color: black; color: white; cursor: pointer;">
                                X
                            </button>
                        </div>
                    </div>`;
            container.html(articleHTML);
            currentShortUrl = null;
            originalUrlInContent = null;
            $('#content-url-shortener-wrap').hide();
            $('#real-article').animate({
                opacity: 1
            }, 1000);
        }
        $(document).on('click', '.close-btn-placeholder', function() {
            resetPostArea();
        });

        // URL shortener: when checkbox is checked, shorten the link and display it
        $(document).on('change', '#use_short_link', function() {
            var $cb = $(this);
            var $result = $('#short-link-result');
            var $display = $('#short_link_url_display');
            if (!$cb.is(':checked')) {
                currentShortUrl = null;
                $result.hide();
                $display.val('');
                return;
            }
            var originalUrl = $('.link_url').text().trim();
            if (!originalUrl) {
                toastr.warning('No link to shorten.');
                $cb.prop('checked', false);
                return;
            }
            $result.hide();
            $display.val('Shortening...');
            $result.show();
            $.ajax({
                url: "{{ route('general.shorten') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "original_url": originalUrl
                },
                success: function(res) {
                    if (res.success && res.short_url) {
                        currentShortUrl = res.short_url;
                        $display.val(res.short_url);
                    } else {
                        currentShortUrl = null;
                        $display.val('');
                        $result.hide();
                        toastr.error(res.message || 'Could not shorten link.');
                        $cb.prop('checked', false);
                    }
                },
                error: function(xhr) {
                    currentShortUrl = null;
                    $display.val('');
                    $result.hide();
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr
                        .responseJSON.message : 'Could not shorten link.');
                    $cb.prop('checked', false);
                }
            });
        });

        $(document).on('click', '.copy-short-link', function() {
            var url = $('#short_link_url_display').val();
            if (url && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    toastr.success('Short link copied to clipboard.');
                }).catch(function() {
                    fallbackCopyShortLink(url);
                });
            } else {
                fallbackCopyShortLink(url);
            }
        });
        $(document).on('click', '.copy-short-link-content', function() {
            var url = $('#short_link_url_display_content').val();
            if (url && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    toastr.success('Short link copied to clipboard.');
                }).catch(function() {
                    fallbackCopyShortLink(url);
                });
            } else {
                fallbackCopyShortLink(url);
            }
        });

        function fallbackCopyShortLink(text) {
            var $input = $('<input>').val(text).appendTo('body').select();
            try {
                document.execCommand('copy');
                toastr.success('Short link copied to clipboard.');
            } catch (e) {
                toastr.info('Short link: ' + text);
            }
            $input.remove();
        }
        // Content shortener (when link is in textarea but post is photo/content)
        $(document).on('change', '#use_short_link_content', function() {
            var $cb = $(this);
            var $result = $('#short-link-result-content');
            var $display = $('#short_link_url_display_content');
            if (!$cb.is(':checked')) {
                currentShortUrl = null;
                originalUrlInContent = null;
                $result.hide();
                $display.val('');
                return;
            }
            var originalUrl = extractUrlFromContent($("#content").val());
            if (!originalUrl) {
                toastr.warning('No link found in your post to shorten.');
                $cb.prop('checked', false);
                return;
            }
            originalUrlInContent = originalUrl;
            $result.show();
            $display.val('Shortening...');
            $.ajax({
                url: "{{ route('general.shorten') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "original_url": originalUrl
                },
                success: function(res) {
                    if (res.success && res.short_url) {
                        currentShortUrl = res.short_url;
                        $display.val(res.short_url);
                    } else {
                        currentShortUrl = null;
                        originalUrlInContent = null;
                        $display.val('');
                        $result.hide();
                        toastr.error(res.message || 'Could not shorten link.');
                        $cb.prop('checked', false);
                    }
                },
                error: function(xhr) {
                    currentShortUrl = null;
                    originalUrlInContent = null;
                    $display.val('');
                    $result.hide();
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr
                        .responseJSON.message : 'Could not shorten link.');
                    $cb.prop('checked', false);
                }
            });
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

            // Get selected accounts from account cards
            var selectedAccounts = getSelectedAccounts();

            // If no account is selected, show empty posts
            if (selectedAccounts.accountIds.length === 0) {
                $('#postsGrid').html(`
                    <div class="empty-state text-center py-5" style="grid-column: 1/-1;">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Please select an account above to view posts.</p>
                    </div>
                `);
                totalPosts = 0;
                renderPagination();
                return;
            }

            $.ajax({
                url: "{{ route('panel.schedule.posts.listing') }}",
                type: "GET",
                data: {
                    draw: 1,
                    start: (page - 1) * perPage,
                    length: perPage,
                    account_id: selectedAccounts.accountIds,
                    type: selectedAccounts.accountTypes,
                    post_type: $("#filter_post_type").val(),
                    status: getStatusFilterValue(),
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
            var platformIcon = post.social_type === 'facebook' ? 'fab fa-facebook-f' : (post.social_type ===
                'pinterest' ? 'fab fa-pinterest-p' : 'fab fa-tiktok');
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

        // Get status filter value (handle "all" option)
        function getStatusFilterValue() {
            var statusValues = $("#filter_status").val();
            if (!statusValues || statusValues.length === 0) {
                return [];
            }
            // If "all" is selected, return empty array to show all statuses
            if (statusValues.includes('all')) {
                return [];
            }
            return statusValues;
        }

        // Handle "All Status" option in status filter
        $(document).on('change', '#filter_status', function() {
            var selectedValues = $(this).val();
            if (!selectedValues) {
                selectedValues = [];
            }

            var $select = $(this);
            var hasAll = selectedValues.includes('all');
            var individualStatuses = ['0', '1', '-1'];
            var hasIndividualStatuses = individualStatuses.some(function(status) {
                return selectedValues.includes(status);
            });

            // If "all" is selected
            if (hasAll) {
                // If "all" was just selected, deselect individual statuses to avoid confusion
                if (hasIndividualStatuses) {
                    $select.val(['all']).trigger('change.select2');
                }
            } else {
                // If all individual statuses are selected, automatically select "all"
                var allSelected = individualStatuses.every(function(status) {
                    return selectedValues.includes(status);
                });
                if (allSelected && selectedValues.length === 3) {
                    $select.val(['all']).trigger('change.select2');
                    return; // Don't reload yet, let the change event trigger again
                }
            }

            // Reload posts with updated filter
            loadPosts(1);
        });

        // Filter change (for other filters)
        $(document).on('change', '.filter:not(#filter_status)', function() {
            loadPosts(1);
        });

        // Reload posts function (for use after actions)
        var reloadPosts = function() {
            loadPosts(currentPage);
        }

        // Track last notification count to detect new notifications
        var lastNotificationCount = 0;

        // Function to check for new notifications and refresh posts
        function checkNotificationsAndRefresh() {
            $.ajax({
                url: '{{ route('panel.notifications.fetch') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var currentCount = response.count || 0;
                        // If notification count increased, refresh posts
                        if (currentCount > lastNotificationCount && lastNotificationCount > 0) {
                            // New notification received, refresh posts
                            reloadPosts();
                        }
                        lastNotificationCount = currentCount;
                    }
                },
                error: function(xhr) {}
            });
        }

        // Set up notification polling to refresh posts when new notifications arrive
        // Poll every 5 seconds (same as notification refresh interval)
        var notificationCheckInterval = setInterval(checkNotificationsAndRefresh, 5000);

        // Initial notification count fetch
        checkNotificationsAndRefresh();

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

        function showTikTokModal(file, accounts) {
            currentTikTokFile = file;
            currentTikTokAccounts = Array.isArray(accounts) ? accounts : [accounts];

            // Reset modal
            resetTikTokModal();

            // Set account ID (use first account)
            if (currentTikTokAccounts.length > 0) {
                $('#tiktok-account-id').val(currentTikTokAccounts[0].id);
            }

            // Determine post type
            var isVideo = file.type.startsWith('video/');
            $('#tiktok-post-type').val(isVideo ? 'video' : 'photo');

            // Show preview
            showTikTokPreview(file);

            // Display account names
            displayTikTokAccountNames(currentTikTokAccounts);

            // Populate form options
            populateTikTokFormOptions();

            // Show modal
            $('.tiktok-post-modal').modal('show');
        }

        function resetTikTokModal() {
            $('#tiktok-title').val('');
            $('#tiktok-privacy-level').val('').html('<option value="">-- Select Privacy Level --</option>');
            $('#tiktok-allow-comment').prop('checked', false);
            $('#tiktok-allow-duet').prop('checked', false);
            $('#tiktok-allow-stitch').prop('checked', false);
            $('#tiktok-commercial-toggle').prop('checked', false);
            $('#tiktok-your-brand').prop('checked', false);
            $('#tiktok-branded-content').prop('checked', false);
            $('#commercial-options').hide();
            $('#commercial-prompts').html('');
            $('#commercial-error').hide();
            $('#branded-content-privacy-warning').hide();
            $('#tiktok-publish-btn').prop('disabled', true);
            $('#tiktok-account-names').html('');
            $('#content-preview').hide();
            $('#preview-image').hide();
            $('#preview-video').hide();
            $('#preview-title').text('');
            $('#title-char-count').text('0');
            currentTikTokFile = null;
            currentTikTokLinkUrl = null;
            currentTikTokLinkImage = null;
            currentTikTokScheduleDate = null;
            currentTikTokScheduleTime = null;
        }

        // Show TikTok modal for link posts
        // Note: For link posts, no file in dropzone is needed since fetchLink already provides the image
        function showTikTokLinkModal(title, url, imageUrl, accounts, scheduleDate, scheduleTime) {
            currentTikTokAccounts = Array.isArray(accounts) ? accounts : [accounts];
            currentTikTokLinkUrl = url;
            currentTikTokLinkImage = imageUrl; // Use fetched link image, no dropzone file needed
            currentTikTokScheduleDate = scheduleDate;
            currentTikTokScheduleTime = scheduleTime;

            // Set account ID (use first account)
            if (currentTikTokAccounts.length > 0) {
                $('#tiktok-account-id').val(currentTikTokAccounts[0].id);
            }

            // Set post type to photo (since links are converted to photos)
            $('#tiktok-post-type').val('photo');
            $('#tiktok-file-url').val(imageUrl); // Use fetched link image URL

            // Show link image preview (from fetched link, not dropzone)
            $('#preview-image').show().find('img').attr('src', imageUrl);
            $('#preview-video').hide();
            $('#preview-title').text(title);
            $('#content-preview').show();

            // Pre-fill title with link URL (user can edit it)
            $('#tiktok-title').val(title);
            $('#title-char-count').text(title.length);

            // Display account names
            displayTikTokAccountNames(currentTikTokAccounts);

            // Populate form options
            populateTikTokFormOptions();

            // Show modal
            $('.tiktok-post-modal').modal('show');
        }

        function displayTikTokAccountNames(accounts) {
            var namesHtml = '';
            accounts.forEach(function(account) {
                var accountCard = $('.account-card[data-type="tiktok"][data-id="' + account.id + '"]');
                var accountName = accountCard.find('.account-name').text().trim() || account.name ||
                    'TikTok Account';
                var accountUsername = accountCard.find('.account-username').text().trim() || account
                    .username || '';

                namesHtml += '<div class="mb-1">';
                namesHtml += '<strong>' + accountName + '</strong>';
                if (accountUsername) {
                    namesHtml += ' <small class="text-muted">(@' + accountUsername + ')</small>';
                }
                namesHtml += '</div>';
            });
            $('#tiktok-account-names').html(namesHtml);
        }

        function populateTikTokFormOptions() {
            // Populate privacy options with defaults
            var select = $('#tiktok-privacy-level');
            select.html('<option value="">-- Select Privacy Level --</option>');
            var defaultOptions = ['PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'FOLLOWER_OF_CREATOR',
                'SELF_ONLY'
            ];
            defaultOptions.forEach(function(option) {
                var label = option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                select.append($('<option></option>').attr('value', option).text(label));
            });

            // Hide Duet and Stitch for photo posts
            var isPhoto = $('#tiktok-post-type').val() === 'photo';
            if (isPhoto) {
                $('#duet-container').hide();
                $('#stitch-container').hide();
            } else {
                $('#duet-container').show();
                $('#stitch-container').show();
            }

            // Check video duration if video
            if (!isPhoto && currentTikTokFile) {
                checkVideoDuration();
            }
        }

        function checkVideoDuration() {
            if ($('#tiktok-post-type').val() === 'video' && currentTikTokFile) {
                var video = document.createElement('video');
                video.preload = 'metadata';
                video.onloadedmetadata = function() {
                    window.URL.revokeObjectURL(video.src);
                    var duration = video.duration;
                    $('#tiktok-video-duration').val(duration);
                };
                video.src = URL.createObjectURL(currentTikTokFile);
            }
        }

        function showTikTokPreview(file) {
            var previewDiv = $('#content-preview');
            var previewImage = $('#preview-image');
            var previewVideo = $('#preview-video');
            var previewTitle = $('#preview-title');

            previewImage.hide();
            previewVideo.hide();

            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.find('img').attr('src', e.target.result);
                    previewImage.show();
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewVideo.find('video').attr('src', e.target.result);
                    previewVideo.show();
                };
                reader.readAsDataURL(file);
            }

            previewTitle.text(file.name);
            previewDiv.show();
        }


        // Commercial content toggle handler
        $('#tiktok-commercial-toggle').on('change', function() {
            if ($(this).is(':checked')) {
                $('#commercial-options').show();
                updateCommercialPrompts();
                validateTikTokForm();
            } else {
                $('#commercial-options').hide();
                $('#tiktok-your-brand').prop('checked', false);
                $('#tiktok-branded-content').prop('checked', false);
                updateCommercialPrompts();
                updateDeclaration();
                validateTikTokForm();
            }
        });

        $('#tiktok-your-brand, #tiktok-branded-content').on('change', function() {
            updateCommercialPrompts();
            updateDeclaration();
            validateTikTokForm();
        });

        function updateCommercialPrompts() {
            var yourBrand = $('#tiktok-your-brand').is(':checked');
            var brandedContent = $('#tiktok-branded-content').is(':checked');
            var prompts = $('#commercial-prompts');
            prompts.html('');

            if (yourBrand && !brandedContent) {
                prompts.html(
                    '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Your photo/video will be labeled as \'Promotional content\'</div>'
                );
            } else if (!yourBrand && brandedContent) {
                prompts.html(
                    '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Your photo/video will be labeled as \'Paid partnership\'</div>'
                );
            } else if (yourBrand && brandedContent) {
                prompts.html(
                    '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Your photo/video will be labeled as \'Paid partnership\'</div>'
                );
            }
        }

        function updateDeclaration() {
            var commercialToggle = $('#tiktok-commercial-toggle').is(':checked');
            var yourBrand = $('#tiktok-your-brand').is(':checked');
            var brandedContent = $('#tiktok-branded-content').is(':checked');
            var declaration = $('#tiktok-declaration');

            if (commercialToggle && (yourBrand || brandedContent)) {
                if (brandedContent) {
                    declaration.html(
                        '<i class="fas fa-exclamation-circle"></i> <strong>By posting, you agree to TikTok\'s Branded Content Policy and Music Usage Confirmation</strong>'
                    );
                } else {
                    declaration.html(
                        '<i class="fas fa-exclamation-circle"></i> <strong>By posting, you agree to TikTok\'s Music Usage Confirmation</strong>'
                    );
                }
            } else {
                declaration.html(
                    '<i class="fas fa-exclamation-circle"></i> <strong>By posting, you agree to TikTok\'s Music Usage Confirmation</strong>'
                );
            }
        }

        // Privacy level change handler
        $('#tiktok-privacy-level').on('change', function() {
            validateTikTokForm();
            checkBrandedContentPrivacy();
        });

        function checkBrandedContentPrivacy() {
            var brandedContent = $('#tiktok-branded-content').is(':checked');
            var privacyLevel = $('#tiktok-privacy-level').val();
            var warning = $('#branded-content-privacy-warning');

            if (brandedContent && privacyLevel === 'SELF_ONLY') {
                warning.show();
                // Auto-switch to public
                $('#tiktok-privacy-level').val('PUBLIC_TO_EVERYONE');
            } else {
                warning.hide();
            }
        }

        // Title character count
        $('#tiktok-title').on('input', function() {
            var count = $(this).val().length;
            $('#title-char-count').text(count);
            validateTikTokForm();
        });

        function validateTikTokForm() {
            var isValid = true;

            // Check title
            var title = $('#tiktok-title').val().trim();
            if (!title) {
                isValid = false;
            }

            // Check privacy level
            var privacyLevel = $('#tiktok-privacy-level').val();
            if (!privacyLevel) {
                isValid = false;
            }

            // Check commercial content
            var commercialToggle = $('#tiktok-commercial-toggle').is(':checked');
            if (commercialToggle) {
                var yourBrand = $('#tiktok-your-brand').is(':checked');
                var brandedContent = $('#tiktok-branded-content').is(':checked');
                if (!yourBrand && !brandedContent) {
                    isValid = false;
                    $('#commercial-error').show();
                } else {
                    $('#commercial-error').hide();
                }
            }

            // Check branded content privacy
            if ($('#tiktok-branded-content').is(':checked') && $('#tiktok-privacy-level').val() ===
                'SELF_ONLY') {
                isValid = false;
            }

            $('#tiktok-publish-btn').prop('disabled', !isValid);

            return isValid;
        }

        // Publish button handler
        $('#tiktok-publish-btn').on('click', function() {
            if (!validateTikTokForm()) {
                toastr.error('Please fill in all required fields correctly');
                return;
            }

            // Check if this is a link post
            // For link posts, no dropzone file is needed - we use the fetched link image
            var isLinkPost = currentTikTokLinkUrl && currentTikTokLinkImage;

            // Only require file for non-link posts
            if (!isLinkPost && !currentTikTokFile) {
                toastr.error('No file selected');
                return;
            }

            // Prepare form data
            var formData = new FormData();

            if (isLinkPost) {
                // For link posts: use fetched link image (no dropzone file needed)
                // Title comes from modal textarea, image comes from fetched link
                var title = $('#tiktok-title').val();
                var comment = $('#comment').val();
                formData.append('content', title); // Use title from modal textarea
                formData.append('comment', comment);
                formData.append('link', 1);
                formData.append('url', currentTikTokLinkUrl);
                formData.append('image',
                    currentTikTokLinkImage); // Fetched link image, not from dropzone
                if (currentTikTokScheduleDate) {
                    formData.append('schedule_date', currentTikTokScheduleDate);
                }
                if (currentTikTokScheduleTime) {
                    formData.append('schedule_time', currentTikTokScheduleTime);
                }
            } else {
                // For regular file posts (requires dropzone file)
                formData.append('files', currentTikTokFile);
                formData.append('content', $('#tiktok-title').val());
            }

            formData.append('action', action_name);
            formData.append('tiktok_account_id', $('#tiktok-account-id').val());
            formData.append('tiktok_privacy_level', $('#tiktok-privacy-level').val());
            formData.append('tiktok_allow_comment', $('#tiktok-allow-comment').is(':checked') ? 1 : 0);
            formData.append('tiktok_allow_duet', $('#tiktok-allow-duet').is(':checked') ? 1 : 0);
            formData.append('tiktok_allow_stitch', $('#tiktok-allow-stitch').is(':checked') ? 1 : 0);
            formData.append('tiktok_commercial_toggle', $('#tiktok-commercial-toggle').is(':checked') ?
                1 : 0);
            formData.append('tiktok_your_brand', $('#tiktok-your-brand').is(':checked') ? 1 : 0);
            formData.append('tiktok_branded_content', $('#tiktok-branded-content').is(':checked') ? 1 :
                0);
            formData.append('video', $('#tiktok-post-type').val() === 'video' ? 1 : 0);

            // Add CSRF token
            formData.append('_token', '{{ csrf_token() }}');

            // Disable button
            $(this).prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-2"></i>Publishing...');

            // Submit via AJAX
            $.ajax({
                url: '{{ route('panel.schedule.process.post') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('.tiktok-post-modal').modal('hide');

                        if (isLinkPost) {
                            // For link posts: no dropzone files to handle, just reset variables
                            currentTikTokLinkUrl = null;
                            currentTikTokLinkImage = null;
                            currentTikTokScheduleDate = null;
                            currentTikTokScheduleTime = null;
                            resetPostArea();
                        } else {
                            // For file posts: remove file from dropzone and process remaining files
                            if (currentTikTokFile) {
                                dropZone.removeFile(currentTikTokFile);
                            }
                            // Process remaining files
                            var remainingFiles = dropZone.getAcceptedFiles();
                            if (remainingFiles.length > 0) {
                                processQueueWithDelay(remainingFiles);
                            } else {
                                resetPostArea();
                            }
                        }
                    } else {
                        toastr.error(response.message);
                        $('#tiktok-publish-btn').prop('disabled', false).html(
                            '<i class="fas fa-paper-plane mr-2"></i>Publish');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Failed to publish post';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg);
                    $('#tiktok-publish-btn').prop('disabled', false).html(
                        '<i class="fas fa-paper-plane mr-2"></i>Publish');
                }
            });
        });

        // Initialize form validation on modal show
        $('.tiktok-post-modal').on('shown.bs.modal', function() {
            validateTikTokForm();
        });
    });
</script>
