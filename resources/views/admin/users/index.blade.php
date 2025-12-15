@extends('admin.layouts.secure')
@section('page_title', 'Users')
@section('page_content')
    @can('view_user')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Users</h1>
                <div class="float-right">
                    <a class="btn btn-primary" href="{{ route('admin.users.create') }}">
                        <i class="fas fa-plus-square"></i> Add new</a>
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
                                                            <th data-data="name_link">Full Name</th>
                                                            <th data-data="email">Email</th>
                                                            <th data-data="package">Package</th>
                                                            <th data-data="role">Role</th>
                                                            <th data-data="status_span">Status</th>
                                                            <th data-data="action">Action</th>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>1</td>
                                                            </tr>
                                                        </tbody>
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
        $('#dataTable').DataTable({
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
                url: "{{ route('admin.users.dataTable') }}",
            },
            order: [[0, 'desc']],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'name_link',
                    orderable: false
                },
                {
                    data: 'email'
                },
                {
                    data: 'package_html',
                    name: 'package_html',
                    orderable: false
                },
                {
                    data: 'role_name',
                    name: 'role_name'
                },
                {
                    data: 'status_span',
                    orderable: false
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
        });
    </script>
@endpush
