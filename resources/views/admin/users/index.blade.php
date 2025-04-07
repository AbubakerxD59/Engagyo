@extends('admin.layouts.secure')
@section('page_title', 'Users')
@section('page_content')
    @can('view_user')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Users</h1>
                <div class="float-right">
                    <a class="btn btn-primary" href="{{ route('users.create') }}">
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
                                                            <th>{{ __('users.id') }}</th>
                                                            <th>{{ __('users.profile_pic') }}</th>
                                                            <th>{{ __('users.full_name') }}</th>
                                                            <th>Membership ID</th>
                                                            <th>{{ __('users.email_username') }}</th>
                                                            <th>Whatsapp#</th>
                                                            <th>City</th>
                                                            <th>Country</th>
                                                            <th>{{ __('users.role') }}</th>
                                                            <th>{{ __('users.status') }}</th>
                                                            <th>{{ __('users.created_at') }}</th>
                                                            <th>{{ __('users.action') }}</th>
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
                url: "{{ route('users.dataTable') }}",
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
                    data: 'membership_id'
                },
                {
                    data: 'email_username'
                },
                {
                    data: 'whatsapp_number'
                },
                {
                    data: 'city'
                },
                {
                    data: 'country'
                },
                {
                    data: 'role'
                },
                {
                    data: 'status_span'
                },
                {
                    data: 'created'
                },
                {
                    data: 'action'
                }
            ],
        });
    </script>
@endpush
