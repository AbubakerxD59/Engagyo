@extends('admin.layouts.secure')
@section('page_title', 'Promo Codes')
@section('page_content')
    @can('view_promocode')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Promo Codes</h1>
                <div class="float-right">
                    <a class="btn btn-primary" href="{{ route('promo-code.create') }}">
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
                                                            <th>Name</th>
                                                            <th>Code</th>
                                                            <th>Duration</th>
                                                            <th>Discount Type</th>
                                                            <th>Discount Amount</th>
                                                            <th>Status</th>
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
                url: "{{ route('promo-code.dataTable') }}",
            },
            columns: [{
                    data: 'name'
                },
                {
                    data: 'code'
                },
                {
                    data: 'duration_day'
                },
                {
                    data: 'discount_type'
                },
                {
                    data: 'amount'
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
