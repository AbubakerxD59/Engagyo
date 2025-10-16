<script>
    $(document).ready(function() {
        // global variables
        var action_name = '';
        var current_file = 0;
        var is_link = false;
        // character count
        getCharacterCount($('.check_count'));
        // account status
        $(".account").on("click", function() {
            $(this).toggleClass("shadow border-success");
            var type = $(this).data("type");
            var id = $(this).data("id");
            var status = $(this).hasClass("shadow border-success") ? 1 : 0;
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
                        $(this).toggleClass("shadow border-success");
                        toastr.error(response.message);
                    }
                },
                error: function(response) {
                    $(this).toggleClass("shadow border-success");
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
                    var supportedFormats = ['image/jpeg', 'image/jpg', 'image/png',
                        'video/mp4', 'video/quicktime', 'video/mpg', 'video/webm',
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
                    // for schedule action
                    data.append("schedule_date", schedule_date);
                    data.append("schedule_time", schedule_time);
                    data.append("action", action);
                });
                // request success
                this.on("success", function(file, response) {
                    console.log(response);
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
            var isValid = false;
            action_name = $(this).attr("href");
            // schedule posting
            if (action_name == "schedule") {
                var schedule_modal = $(".schedule-modal");
                schedule_modal.modal("toggle");
            } else {
                validateAndProcess();
            }
        });
        $(document).on('click', '.schedule_btn', function() {
            var schedule_date = $('#schedule_date').val();
            var schedule_time = $('#schedule_time').val();
            if (empty(schedule_date) || empty(schedule_time)) {
                toastr.error("Schedule date & time are required!");
                return false;
            }
            validateAndProcess();
        });
        // validate and process post
        var validateAndProcess = function() {
            var isValid = false;
            // check accounts
            $('.account').each(function() {
                if ($(this).hasClass("shadow border-success")) {
                    isValid = true;
                }
            });
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
        // process dropzone queue
        function processQueueWithDelay(filesCopy) {
            disableActionButton();
            if (filesCopy.length > current_file) {
                var file = filesCopy[current_file];
                dropZone.processFile(file);
            } else {
                // All files processed
                resetPostArea
                current_file = 0;
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
                    "link": is_link,
                    "action": action_name
                },
                success: function(response) {
                    if (response.success) {
                        resetPostArea();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                }
            })
        }
        // reset post area
        var resetPostArea = function() {
            is_link = false;
            $('#content').val('');
            $('#comment').val('');
            enableActionButton();
        }
        // check link for content
        $('#content').on('input', function() {
            var value = $(this).val();
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
                            is_link = true;
                            if (!empty(title)) {
                                $("#content").val(response.title);
                                $("#content").trigger("input");
                            }
                            setTimeout(function() {
                                renderArticleContent(response);
                            }, 2000);
                        } else {
                            container.html(
                                '<div style="padding: 1rem; color: #DC2626;">Error loading data. Please try again.</div>'
                            );
                            toastr.error(response.message);
                        }
                        enableActionButton();
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
            $.ajax({
                url: "{{ route('panel.schedule.get.setting') }}",
                type: "GET",
                success: function(response) {
                    if (response.success) {
                        modal.find(".modal-body").html(response.data);
                        modal.modal("toggle");
                        // select2
                        $('.select2').select2({
                            closeOnSelect: false
                        });
                    } else {
                        toastr.error("Something went Wrong!");
                    }
                }
            });
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
                            <h2 class="title">${data.title}</h2>
                            <p class="summary">${data.link}</p>
                        </div>
                        <!-- Right Column (Image/Sidebar) -->
                        <div class="image-col" style="margin-left: 1rem;">
                            <img src="${data.image}" alt="Feature Icon">
                            <!-- Close Button (Functional) -->
                            <button class="close-btn-placeholder" 
                                onclick="$(this).closest('#real-article').remove()" 
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
        // link loading and preview
    });
</script>
