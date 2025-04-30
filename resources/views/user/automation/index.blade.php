@extends('user.layout.main')
@section('title', 'Accounts')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix">
            <h1 class="float-left">Automation</h1>
        </div>
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
                        <div class="row">
                            <form id="adv_filter_form" class="row col-md-12">
                                <div class="col-md-4 form-group">
                                    <label for="account">Accounts</label>
                                    <select name="account" id="account" class="form-control adv_filter">
                                        <option value="">All Accounts</option>
                                        @foreach ($user->getAccounts() as $key => $account)
                                            <option value="{{ $account->id }}" data-type="{{ $account->type }}">
                                                {{ strtoupper($account->name . ' - ' . $account->type) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <a class="btn btn-outline-info btn-sm mt-1">
                                        Last Fetch:
                                        <span class="last_fetch"></span>
                                    </a>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="domains">Domains</label>
                                    <select name="domains[]" id="domains" class="form-control adv_filter select2"
                                        multiple>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="status">Status</label>
                                    <select name="status" id="status" class="form-control adv_filter">
                                        <option value="">All Status</option>
                                        <option value="1">Published</option>
                                        <option value="0">Pending</option>
                                        <option value="-1">Failed</option>
                                    </select>
                                </div>
                                <div class="col-md-12 form-group">
                                    <label for="search">Search</label>
                                    <input type="text" name="search" id="search"
                                        class="form-control adv_filter_search">
                                </div>
                            </form>
                            <div class="col-md-12 row form-group  justify-content-between">
                                <div class="col-md-6">
                                    <button id="clearFilters" class="btn btn-outline-secondary btn-sm ">Clear
                                        Filters
                                    </button>
                                    <button id="postsFetch" class="btn btn-outline-success btn-sm" data-toggle="modal"
                                        data-target="#fetchPostsModal">Fetch Post</button>
                                </div>
                                <div class="col-md-6 d-flex justify-content-end">
                                    <div class="btn shuffle_toggle py-0" style="display: none;">
                                        <div class="d-flex align-items-start">
                                            <span>
                                                Shuffle
                                            </span>
                                            <div class="toggle-switch mx-1">
                                                <input class="toggle-input" id="toggle" type="checkbox">
                                                <label class="toggle-label" for="toggle"></label>
                                            </div>
                                        </div>
                                    </div>
                                    <a class="btn btn-outline-info btn-sm">
                                        Scheduled Till:
                                        <span class="scheduled_till"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <table class="table table-striped table-bordered" id="dataTable">
                            <thead>
                                <tr>
                                    <th>Post</th>
                                    <th>Account</th>
                                    <th>Domain</th>
                                    <th>Publish Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('user.automation.fetch_posts_modal', ['user' => $user])
    @include('user.automation.edit_post_modal')
@endsection
@push('styles')
@endpush
@push('scripts')
    <script>
        var postsDatatable = $('#dataTable').DataTable({
            "paging": true,
            'iDisplayLength': 10,
            "lengthChange": true,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "processing": true,
            "serverSide": true,
            order: [
                [3, 'ASC']
            ],
            ajax: {
                url: "{{ route('panel.automation.posts.dataTable') }}",
                data: function(param) {
                    param.account = $("#account").find(":selected").val();
                    param.account_type = $("#account").find(":selected").val() ? $("#account").find(":selected")
                        .data("type") : 0;
                    param.domain = $("#domains").val();
                    param.status = $("#status").find(":selected").val();
                    param.search_input = $("#search").val();
                    return param;
                },
            },
            drawCallback: function() {
                var api = this.api();
                var response = api.ajax.json();
                $('.scheduled_till').html(response.scheduled_till);
                $('.last_fetch').html(response.last_fetch);
            },
            columns: [{
                    data: 'post',
                    sortable: false
                },
                {
                    data: 'account_name',
                    sortable: false
                },
                {
                    data: 'domain_name',
                    sortable: false
                },
                {
                    data: 'publish',
                },
                {
                    data: 'status_view',
                    sortable: false
                },
                {
                    data: 'action',
                    sortable: false
                }
            ],
        });
    </script>
    <script>
        $(document).ready(function() {
            // fetch feed posts
            $("#fetchPostForm").on('submit', function(event) {
                event.preventDefault();
                var form = $(this);
                var submit_button = form.find("#fetchPostsBtn");
                var selected_account = $("#fetch_account").find(":selected").val();
                var selected_type = $("#fetch_account").find(":selected").data("type");
                var selected_time = $("#time").val();
                var selected_url = $("#feed_url").val();
                var new_url = $(".new_feed_url").val();
                var token = $('meta[name="csrf-token"]').attr('content');
                submit_button.attr('disabled', true);
                new_url = new_url == undefined ? [] : new_url;
                var urls = selected_url.concat(new_url);
                if (urls.length == 0) {
                    toastr.error("Feed URL not selected!");
                    submit_button.attr('disabled', false);
                    return false;
                }
                $.ajax({
                    url: "{{ route('panel.automation.feedUrl') }}",
                    type: "POST",
                    data: {
                        "account": selected_account,
                        "type": selected_type,
                        "time": selected_time,
                        "url": urls,
                        "_token": token,
                    },
                    success: function(response) {
                        submit_button.attr('disabled', false);
                        if (response.success) {
                            $("#fetchPostsModal").modal("toggle");
                            postsDatatable.ajax.reload();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                });
            });
            // add new url input
            $(document).on('click', '#addNewUrl', function() {
                var new_url = $('.new_url');
                new_url.show();
                var new_url_body =
                    '<div class="col-md-12 form-group d-flex justify-content-end new_url_body">';
                new_url_body +=
                    '<button class="btn btn-outline-danger mr-4 new_url_delete_btn"><i class="fa fa-trash"></i></button>';
                new_url_body +=
                    '<input type="text" name="new_feed_url[]" class="form-control new_feed_url" placeholder="Add new Feed URL" required>';
                new_url_body += '</div>';
                new_url.append(new_url_body);
            })
            // delete new url input
            $(document).on("click", ".new_url_delete_btn", function() {
                var delete_button = $(this);
                var new_url = delete_button.closest(".new_url_body");
                new_url.remove();
            })
            // Fetch domains
            $('#account').on('change', function() {
                var account_id = $(this).find(":selected").val();
                var selected_type = $(this).find(":selected").data("type");
                var select = $('#domains');
                select.empty();
                toggleShuffle(account_id);
                if (account_id != '') {
                    fetchDomains(account_id, selected_type, select, 'id');
                }
            });
            // Fetch domains
            $('#fetch_account').on('change', function() {
                var account_id = $(this).find(":selected").val();
                var selected_type = $(this).find(":selected").data("type");
                var select = $('#feed_url');
                select.empty();
                if (account_id != '') {
                    fetchDomains(account_id, selected_type, select, 'name');
                }
            });
            // Fetch domains function
            var fetchDomains = function(account_id, selected_type, select, mode) {
                $.ajax({
                    url: "{{ route('panel.automation.getDomain') }}",
                    method: "GET",
                    data: {
                        "account_id": account_id,
                        "type": selected_type
                    },
                    success: function(response) {
                        if (response.success) {
                            options = response.data;
                            $.each(options, function(index, value) {
                                var option = $("<option></option>");
                                mode == "id" ? option.val(value.id).text(value.name) :
                                    option.val(value.name).text(value.name);
                                select.append(option);
                            });
                        }
                    }
                });
            }
            // Filters and Reset
            $('.adv_filter').on('change', function() {
                postsDatatable.ajax.reload();
            });
            $('.adv_filter_search').on('keyup', function() {
                postsDatatable.ajax.reload();
            })
            $("#clearFilters").on("click", function() {
                $("#adv_filter_form").trigger("reset");
                $("#account").trigger("change");
                postsDatatable.ajax.reload();
            })
            // Delete Post
            $(document).on("click", ".post-delete", function() {
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
                            postsDatatable.ajax.reload();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                });
            })
            // Edit Post
            $(document).on('click', '.post_edit', function() {
                var form = $("#editPostForm");
                var post = $(this).data("body");
                var modal = $("#editPostModal");
                form.trigger("reset");
                modal.find("#post_id").val(post.id);
                modal.find("#post_title").val(post.title);
                modal.find("#post_url").val(post.url);
                modal.find("#post_date").val(post.date);
                modal.find("#post_time").val(post.time);
                modal.find("#post_image_preview").attr("src", post.image);
                modal.modal("toggle");
            })
            // Edit Post Modal
            $(document).on('submit', '#editPostForm', function(event) {
                event.preventDefault();
                var modal = $("#editPostModal");
                var form = $("#editPostForm");
                var token = $('meta[name="csrf-token"]').attr('content');
                var post_id = form.find('#post_id').val();
                var formData = new FormData(this);
                formData.append('_token', token);
                $.ajax({
                    url: "{{ route('panel.automation.posts.update') }}/" + post_id,
                    type: 'POST',
                    contentType: 'multipart/form-data',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            postsDatatable.ajax.reload();
                            modal.modal("toggle");
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                });
            })
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
                                postsDatatable.ajax.reload();
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
                console.log('1');
                if (id != '') {
                    $(".shuffle_toggle").show();
                } else {
                    $(".shuffle_toggle").hide();
                }
            }
            // Shuffle toggle
            $(document).on('click', '#toggle', function() {
                var toggle = $("#toggle");
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
        });
    </script>
    </script>
    <script>
        var imageInput = document.getElementById('post_image');
        var imagePreview = document.getElementById('post_image_preview');
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
    </script>
@endpush
