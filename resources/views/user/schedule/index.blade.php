@extends('user.layout.main')
@section('title', 'Schedule')
@section('page_content')
    <div class="page-content">
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
                        <div class="row">
                            @foreach ($accounts as $account)
                                @if ($account->type == 'facebook')
                                    <button
                                        class="btn btn-sm btn-rounded p-1 pr-3 m-1 border-right rounded-lg account 
                                        @if ($account->schedule_status == 'active') shadow border-success @endif"
                                        data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                        <img style="width:35px;height:35px;" src="{{ $account->facebook?->profile_image }}"
                                            class="rounded-circle mr-2" alt="{{ social_logo('facebook') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                        <b>{{ $account->name }}</b>
                                    </button>
                                @elseif($account->type == 'pinterest')
                                    <button
                                        class="btn btn-sm btn-rounded p-1 pr-3 m-1 border-right rounded-lg account 
                                    @if ($account->schedule_status == 'active') shadow border-success @endif"
                                        data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                        <img style="width:35px;height:35px;" src="{{ $account->pinterest?->profile_image }}"
                                            class="rounded-circle mr-2" alt="{{ social_logo('pinterest') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                        <b>{{ $account->name }}</b>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                        <div class="card-body px-0">
                            <div class="row">
                                <textarea name="content" id="content" class="form-control col-md-12 check_count" placeholder="Paste your link here!"
                                    rows="3" data-max="100"></textarea>
                                <span id="characterCount" class="text-muted"></span>
                            </div>
                            <div class="row">
                                <div class="form-control col-md-12 dropzone" id="dropZone">
                                </div>
                            </div>
                            <div class="row">
                                <textarea name="comment" id="comment" class="form-control col-md-12" placeholder="Comment here!" rows="1"
                                    data-max="100"></textarea>
                            </div>
                            <div class="row justify-content-end mt-2">
                                <button type="button" class="btn btn-outline-success btn-sm publish_btn">
                                    PUBLISH
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // global variables
            var action_name = '';
            var current_file = 0;
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
                maxFiles: 5,
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
                        if (supportedFormats.indexOf(file.type) === -1 ||
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
        });
    </script>
@endpush
