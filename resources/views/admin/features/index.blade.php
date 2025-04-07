@extends('admin.layouts.secure')
@section('page_title', 'Services')
@section('page_content')
    @can('view_user')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Services</h1>
                <div class="float-right">
                    <a class="btn btn-primary" data-toggle="modal" data-target="#add_features_modal">
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
    @include('admin.features.add_features_modal')
@endsection
@push('scripts')
    <script type="text/javascript">
        // server side dataTable
        var dataTable = $('#dataTable').DataTable({
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
                url: "{{ route('features.dataTable') }}",
            },
            columns: [{
                    data: 'id'
                },
                {
                    data: 'name'
                },
                {
                    data: 'action'
                }
            ],
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#featureForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData();
                var feature_id = $('#feature_id').val();
                var feature_name = $('#feature_name').val();
                var token = $('meta[name="csrf-token"]').attr('content');

                formData.append('_token', token);
                formData.append('feature_id', feature_id);
                formData.append('name', feature_name);

                $.ajax({
                    url: "{{ route('features.store') }}",
                    type: 'POST',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,
                    success: function(response) {
                        if (response.status) {
                            toastr.success(response.message);
                            dataTable.ajax.reload();
                            $("#featureForm").trigger('reset');
                        }
                    },
                    error: function(response) {
                        response = response.responseJSON;
                        $.each(errors, function(index, error) {
                            toastr.error(error);
                        });
                    }
                });
            });

            $(document).on('click', '.edit_feature', function() {
                var feature_id = $(this).data('feature-id');
                var url = $(this).data('edit-url');
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        if (response.status) {
                            data = response.data;
                            $('#feature_name').val(data.name);
                            $('#feature_id').val(data.id);
                            $('#add_features_modal').modal('toggle');
                        } else {
                            toastr.error(response.error);
                        }
                    },
                    error: function(response) {
                        response = response.responseJSON;
                        errors = response.errors;
                        $.each(errors, function(index, error) {
                            toastr.error(error);
                        });
                    }
                });
            });
        });
    </script>
@endpush
