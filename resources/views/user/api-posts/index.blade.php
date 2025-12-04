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
                        <table class="table table-bordered mt-3" id="postsTable">
                            <thead>
                                <tr>
                                    <th>Post <small>(Details)</small></th>
                                    <th>Account</th>
                                    <th>API Key</th>
                                    <th>Publish Date/Time</th>
                                    <th>Status</th>
                                    <th style="max-width:200px;">Response</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('user.api-posts.modals.edit-post-modal')
@endsection

@push('styles')
    @include('user.schedule.assets.style')
    @include('user.schedule.assets.facebook_post')
    @include('user.schedule.assets.pinterest_post')
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $(".select2").select2({
                placeholder: "Select",
                allowClear: true,
            });

            // Initialize DataTable
            var postsTable = $("#postsTable").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('panel.api-posts.posts.listing') }}",
                    type: "GET",
                    data: function(d) {
                        d.account_id = $("#account").val();
                        d.type = $("#type").val();
                        d.post_type = $("#post_type").val();
                        d.status = $("#status").val();
                    },
                },
                columns: [{
                        data: "post_details",
                        name: "post_details",
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: "account_detail",
                        name: "account_detail",
                        orderable: false,
                        searchable: false,
                        className: 'dt-nowrap'
                    },
                    {
                        data: "api_key_name",
                        name: "api_key_name",
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (data && data !== '-') {
                                return '<span class="badge badge-info"><i class="fas fa-key mr-1"></i>' +
                                    data + '</span>';
                            }
                            return '<span class="text-muted">-</span>';
                        }
                    },
                    {
                        data: "publish_datetime",
                        name: "publish_datetime",
                        orderable: false,
                        searchable: false,
                        className: 'dt-nowrap'
                    },
                    {
                        data: "status_view",
                        name: "status_view",
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: "response",
                        name: "response",
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: "action",
                        name: "action",
                        orderable: false,
                        searchable: false,
                        className: 'dt-nowrap'
                    },
                ],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                language: {
                    emptyTable: "No API posts found",
                    zeroRecords: "No matching posts found",
                },
            });

            // Refresh on filter change
            $(".filter").on("change", function() {
                postsTable.ajax.reload();
            });

            // Delete post
            $(document).on("click", ".delete_btn", function() {
                let id = $(this).data("id");
                if (confirm("Are you sure you want to delete this post?")) {
                    $.ajax({
                        url: "{{ route('panel.api-posts.post.delete') }}",
                        type: "GET",
                        data: {
                            id: id,
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                postsTable.ajax.reload();
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
                        id: id,
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
                            postsTable.ajax.reload();
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
                                postsTable.ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                    });
                }
            });
        });
    </script>
@endpush
