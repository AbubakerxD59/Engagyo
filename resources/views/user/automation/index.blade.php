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
                            <div class="col-md-4 form-group">
                                <label for="account">Accounts</label>
                                <select name="account" id="account" class="form-control">
                                    <option value="">All Accounts</option>
                                    @foreach ($user->getAccounts() as $key => $account)
                                        <option value="{{ $account->id }}">{{ $account->username }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="domains">Domains</label>
                                <select name="domains" id="domains" class="form-control">
                                    <option value="">All Domains</option>
                                    @foreach ($user->getDomains() as $key => $domains)
                                        <option value="{{ $domains->id }}">{{ $domains->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="1">Published</option>
                                    <option value="0">Pending</option>
                                    <option value="-1">Failed</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group mt-4">
                                <button id="searchButton" class="btn btn-primary">Search</button>
                                <button id="clearFilters" class="btn btn-secondary">Clear Filters</button>
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
@endsection
@push('styles')
@endpush
@push('scripts')
    <script>
        $('#dataTable').DataTable({
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
                url: "{{ route('panel.automation.posts') }}",
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
@endpush
