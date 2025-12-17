@extends('admin.layouts.secure')
@section('page_title', 'Features')
@section('page_content')
    @can('view_feature')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Features</h1>
                <div class="float-right">
                    {{-- @can('add_feature')
                        <a class="btn btn-primary" href="{{ route('admin.features.create') }}">
                            <i class="fas fa-plus-square"></i> Add new</a>
                    @endcan --}}
                </div>
            </div>
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-list">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered" id="dataTable">
                                                        <thead>
                                                            <th data-data="id">ID</th>
                                                            <th data-data="name">Name</th>
                                                            <th data-data="is_active">Status</th>
                                                            <th data-data="created">Created</th>
                                                            {{-- <th data-data="action">Action</th> --}}
                                                        </thead>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endcan
@endsection
@push('scripts')
    <script type="text/javascript">
        // server side dataTable
        var dataTable = $('#dataTable').DataTable({
            "paging": true,
            'iDisplayLength': 10,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "processing": true,
            "serverSide": true,
            ajax: {
                url: "{{ route('admin.features.dataTable') }}",
            },
            order: [[0, 'desc']],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'name'
                },
                {
                    data: 'is_active',
                    render: function(data) {
                        return data ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
                    }
                },
                {
                    data: 'created'
                },
                // {
                //     data: 'action',
                //     orderable: false,
                //     searchable: false
                // }
            ],
        });
    </script>
@endpush
