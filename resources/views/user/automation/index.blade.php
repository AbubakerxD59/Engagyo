@extends('user.layout.main')
@section('title', 'Accounts')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix">
            <h1 class="float-left">Automation</h1>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="card-body">
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
            "searching": true,
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
