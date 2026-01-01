@extends('admin.layouts.secure')
@section('page_title', 'TikTok Test Cases')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix">
            <h1 class="float-left">TikTok Test Cases</h1>
            <div class="float-right">
                <button class="btn btn-primary" id="runTestsBtn">
                    <i class="fas fa-play"></i> Run Tests Now
                </button>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Test Cases</h3>
                                <div class="card-tools">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <select class="form-control form-control-sm" id="filterTestType">
                                                <option value="">All Types</option>
                                                <option value="image">Image</option>
                                                <option value="video">Video</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-control form-control-sm" id="filterStatus">
                                                <option value="">All Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="passed">Passed</option>
                                                <option value="failed">Failed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control form-control-sm" id="filterDateFrom"
                                                placeholder="From Date">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control form-control-sm" id="filterDateTo"
                                                placeholder="To Date">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-list">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered" id="dataTable">
                                                    <thead>
                                                        <tr>
                                                            <th data-data="test_type">Test Type</th>
                                                            <th data-data="status_badge">Status</th>
                                                            <th data-data="failure_reason">Failure Reason</th>
                                                            <th data-data="account_name">Account</th>
                                                            <th data-data="ran_at">Ran At</th>
                                                            <th data-data="action">Action</th>
                                                        </tr>
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
@endsection
@push('scripts')
    <script type="text/javascript">
        let table = $('#dataTable').DataTable({
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
                url: "{{ route('admin.tiktok-tests.dataTable') }}",
                data: function(d) {
                    d.test_type = $('#filterTestType').val();
                    d.status = $('#filterStatus').val();
                    d.date_from = $('#filterDateFrom').val();
                    d.date_to = $('#filterDateTo').val();
                }
            },
            order: [
                [0, 'desc']
            ],
            columns: [{
                    data: 'test_type'
                },
                {
                    data: 'status_badge',
                    orderable: false
                },
                {
                    data: 'failure_reason',
                    orderable: false
                },
                {
                    data: 'account_name'
                },
                {
                    data: 'ran_at'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
        });

        $('#filterTestType, #filterStatus, #filterDateFrom, #filterDateTo').on('change', function() {
            table.draw();
        });

        $('#runTestsBtn').on('click', function() {
            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Running...');

            $.ajax({
                url: "{{ route('admin.tiktok-tests.run') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Tests started successfully! Check the results in a few moments.');
                        table.draw();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to run tests'));
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to run tests';
                    alert('Error: ' + errorMsg);
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    </script>
@endpush
