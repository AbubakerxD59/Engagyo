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
                                                {{ $account->username }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="domains">Domains</label>
                                    <select name="domains" id="domains" class="form-control adv_filter">
                                        <option value="">All Domains</option>
                                        @foreach ($user->getDomains() as $key => $domains)
                                            <option value="{{ $domains->id }}">{{ $domains->name }}</option>
                                        @endforeach
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
                            <div class="col-md-4 form-group">
                                <button id="clearFilters" class="btn btn-outline-secondary btn-sm">Clear
                                    Filters
                                </button>
                                <button id="postsFetch" class="btn btn-outline-info btn-sm" data-toggle="modal"
                                    data-target="#fetchPostsModal">Fetch Post</button>
                            </div>
                        </div>
                        <table class="table table-striped table-bordered" id="dataTable">
                            <thead>
                                <tr>
                                    <th>Post</th>
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
            "ordering": false,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "processing": true,
            "serverSide": true,
            ajax: {
                url: "{{ route('panel.automation.posts.dataTable') }}",
                data: function(param) {
                    param.account = $("#account").find(":selected").val();
                    param.type = $("#account").find(":selected").data('type');
                    param.domain = $("#domains").find(":selected").val();
                    param.status = $("#status").find(":selected").val();
                    param.search_input = $("#search").val();
                    return param;
                },
            },
            columns: [{
                    data: 'post'
                },
                {
                    data: 'domain_name',
                },
                {
                    data: 'publish'
                },
                {
                    data: 'status_view'
                },
                {
                    data: 'action'
                }
            ],
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#fetchPostsBtn").on('click', function() {
                var selected_account = $("#fetch_account").find(":selected").val();
                var selected_type = $("#fetch_account").find(":selected").data("type");
                var selected_time = $("#time").val();
                var selected_url = $("#feed_url").val();
                var token = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: "{{ route('panel.automation.feedUrl') }}",
                    type: "POST",
                    data: {
                        "account": selected_account,
                        "type": selected_type,
                        "time": selected_time,
                        "url": selected_url,
                        "_token": token,
                    },
                    success: function(response) {
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

            $('.adv_filter').on('change', function() {
                postsDatatable.ajax.reload();
            });

            $('.adv_filter_search').on('keydown', function() {
                postsDatatable.ajax.reload();
            })

            $("#clearFilters").on("click", function() {
                $("#adv_filter_form").trigger("reset");
                postsDatatable.ajax.reload();
            })

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
        });
    </script>
@endpush
