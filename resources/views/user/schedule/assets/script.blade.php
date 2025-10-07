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
                    var action = action_name

                    data.append("content", content);
                    data.append("comment", comment);
                    data.append("action", action);
                });
                // request success
                this.on("success", function(file, response) {
                    response = response.original;
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
        // publish post
        $('.publish_btn').on('click', function() {
            var isValid = false;
            action_name = "publish";
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
        });
        // process dropzone queue
        function processQueueWithDelay(filesCopy) {
            console.log(filesCopy);
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
            var content = $('#content').val();
            var comment = $('#comment').val();
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "content": content,
                    "comment": comment,
                    "action": action_name
                },
                success: function(response) {
                    response = response.original;
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
            $('#content').val('');
            $('#comment').val('');
        }
        // check link for content
        $('#content').on('input', function() {
            var value = $(this).val();
            if (checkLink(value)) {
                is_link = true;
                var link_data = fetchFromLink(value);
                console.log(link_data);
            }
        });
        // fetch from link
        var fetchFromLink = function(link) {
            if (link) {
                $.ajax({
                    url: "{{ route('general.previewLink') }}",
                    type: "GET",
                    data: {
                        "link": link,
                    },
                    success: function(response) {
                        if (response.success) {
                            var title = response.title;
                            var image = response.image
                            if (!empty(title)) {
                                $("#content").val(response.title);
                                $("#content").trigger("input");
                            }
                            if (!empty(image)) {
                                var mock_image = {
                                    name: "",
                                    size: 54321,
                                    url: image
                                };
                                // Emit dropZone events
                                dropZone.emit("addedfile", mock_image);
                                dropZone.emit("thumbnail", mock_image, image);
                                dropZone.emit("complete", mock_image);
                                // Push file to dropZone
                                dropZone.files.push(mock_image);
                            } else {
                                toastr.error("Unable to fetch Image!");
                            }

                        } else {
                            toastr.error(response.message);
                        }
                    }
                });
            }
        };
    });
</script>
