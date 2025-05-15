@extends('admin.layouts.secure')
@section('page_title', 'Packages')
@section('page_content')
    @can('view_user')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Packages</h1>
                <div class="float-right">
                    <a class="btn btn-primary" href="{{ route('admin.packages.create') }}">
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
                                                            <th>Icon</th>
                                                            <th>Name</th>
                                                            <th>Duration</th>
                                                            <th>Price</th>
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
                url: "{{ route('admin.packages.dataTable') }}",
            },
            columns: [{
                    data: 'icon_view'
                },
                {
                    data: 'name'
                },
                {
                    data: 'time_duration'
                },
                {
                    data: 'price'
                },
                {
                    data: 'action'
                }
            ],
        });
    </script>
@endpush
