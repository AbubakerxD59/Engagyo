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
                                                            <th>Id</th>
                                                            <th>Profile Pic</th>
                                                            <th>Full Name</th>
                                                            <th>Email</th>
                                                            <th>Username</th>
                                                            <th>Country</th>
                                                            <th>City</th>
                                                            <th>Role</th>
                                                            <th>Status</th>
                                                            <th>Action</th>
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
            "ordering": false,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "processing": true,
            "serverSide": true,
            ajax: {
                url: "{{ route('admin.users.dataTable') }}",
            },
            columns: [{
                    data: 'id'
                },
                {
                    data: 'profile',
                },
                {
                    data: 'name_link'
                },
                {
                    data: 'email'
                },
                {
                    data: 'username'
                },
                {
                    data: 'country'
                },
                {
                    data: 'city'
                },
                {
                    data: 'role'
                },
                {
                    data: 'status_span'
                },
                {
                    data: 'action'
                }
            ],
        });
    </script>
@endpush
