@extends('admin.layouts.secure')
@section('page_title', 'Roles')
@section('page_content')
    @can('view_role')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Roles</h1>
                <div class="float-right">
                    <a class="btn btn-primary" href="{{ route('roles.create') }}">
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
                                                            <th>ID</th>
                                                            <th>Name</th>
                                                            <th>Action</th>
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
                url: "{{ route('roles.dataTable') }}",
            },
            columns: [{
                    data: 'id'
                },
                {
                    data: 'name'
                },
                {
                    data: 'action'
                },
            ],
        });
        $(document).on('click', '.check-section-permissions', function() {
            var section_id = $(this).attr('data-id');
            if ($(this).is(':checked')) {
                $(".permission-check-" + section_id).prop('checked', true);
            } else {
                $(".permission-check-" + section_id).prop('checked', false);
            }
        });
    </script>
@endpush
