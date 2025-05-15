@extends('admin.layouts.secure')
@section('page_title', 'Edit Package')
@section('page_content')
    @can('edit_package')
        <div class="page-content">
            <form method="POST" action="{{ route('admin.packages.update', $package->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="content-header clearfix">
                    <h1 class="float-left"> Edit Package
                        <small>
                            <i class="fas fa-arrow-circle-left"></i>
                            <a href="{{ route('admin.packages.index') }}">back to Packages list</a>
                        </small>
                    </h1>
                    <div class="float-right">
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            <i class="far fa-save"></i>
                            Save
                        </button>
                    </div>
                </div>
                <section class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <i class="fas fa-info"></i>
                                            Info
                                        </div>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                                title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="name" class="form-label">Name</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="name" id="name"
                                                    value="{{ old('name', $package->name) }}" placeholder="Enter package name"
                                                    required>
                                                @error('name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="icon" class="form-label">Icon</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="file" name="icon" id="icon" class="form-control">
                                                <img id="iconPreview" class="rounded mt-1" width="150px"
                                                    src="{{ $package->icon }}">

                                                @error('icon')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="duration" class="form-label">Duration</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" class="form-control" name="duration" id="duration"
                                                    value="{{ old('duration', $package->duration) }}"
                                                    placeholder="Enter package duration" required>
                                                @error('duration')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-5">
                                                <select class="form-control" name="date_type" id="date_type">
                                                    <option value="day" {{ $package->date_type == 'day' ? 'selected' : '' }}>
                                                        Day(s)</option>
                                                    <option value="month"
                                                        {{ $package->date_type == 'month' ? 'selected' : '' }}>Month(s)</option>
                                                    <option value="year"
                                                        {{ $package->date_type == 'year' ? 'selected' : '' }}>Year(s)</option>
                                                </select>
                                                @error('name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="price" class="form-label">Price</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="number" class="form-control" name="price" id="price"
                                                    value="{{ old('price', $package->price) }}"
                                                    placeholder="Enter package price" required>
                                                @error('price')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="stripe_product_id" class="form-label">Stripe Product ID</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="stripe_product_id"
                                                    id="stripe_product_id"
                                                    value="{{ old('stripe_product_id', $package->stripe_product_id) }}"
                                                    placeholder="Enter package product ID" disabled>
                                                @error('stripe_product_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="stripe_price_id" class="form-label">Stripe Price ID</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="stripe_price_id"
                                                    id="stripe_price_id"
                                                    value="{{ old('stripe_price_id', $package->stripe_price_id) }}"
                                                    placeholder="Enter package product ID" disabled>
                                                @error('stripe_price_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header with-border clearfix">
                                        <div class="card-title">
                                            <i class="fas fa-star"></i>
                                            Services
                                        </div>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                                title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @can('add_feature')
                                            <div class="content-header clearfix">
                                                <div class="float-right">
                                                    <a class="btn btn-primary" data-toggle="modal"
                                                        data-target="#add_features_modal">
                                                        <i class="fas fa-plus-square"></i> Add Service</a>
                                                </div>
                                            </div>
                                        @endcan
                                        <div class="table-list">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="table-responsive">
                                                        <?php
                                                        $selected_features = [];
                                                        ?>
                                                        <table class="table table-striped table-bordered"
                                                            id="features_dataTable">
                                                            <thead>
                                                                <th>ID</th>
                                                                <th>Name</th>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($package->load('features')->features as $key => $feature)
                                                                    <?php
                                                                    array_push($selected_features, $feature->id);
                                                                    ?>
                                                                    <tr>
                                                                        <td>{{ $key + 1 }}</td>
                                                                        <td>{{ $feature->name }}</td>
                                                                    </tr>
                                                                @endforeach
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
            </form>
        </div>
    @endcan
    @include('admin.packages.add_features_modal', [
        'features' => $features,
        'package' => $package,
        'selected_features' => $selected_features,
    ])
@endsection
@push('scripts')
    <script>
        var photoInput = document.getElementById('icon');
        var photoPreview = document.getElementById('iconPreview');

        photoInput.addEventListener('change', function(event) {
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                photoPreview.src = "/images/noimage.png";
            }
        });
    </script>
    <script>
        var features_dataTable = $('#features_dataTable').DataTable({
            "paging": true,
            'iDisplayLength': 10,
            "lengthChange": true,
            "searching": true,
            "ordering": false,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });
    </script>
    <script>
        $('#featureForm').on('submit', function(event) {
            event.preventDefault();
            var formData = new FormData();
            var package_id = $('#package_id').val();
            var token = $('meta[name="csrf-token"]').attr('content');
            var features = $('.feature_id');
            var feature_ids = [];
            $.each(features, function(index, value) {
                if ($(this).is(':checked')) {
                    feature_ids.push($(this).val());
                }
            });
            formData.append('_token', token);
            formData.append('package_id', package_id);
            formData.append('feature_ids', feature_ids);

            $.ajax({
                url: "{{ route('admin.packages.add_facility') }}",
                type: 'POST',
                cache: false,
                contentType: false,
                processData: false,
                data: formData,
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message);
                        $('#add_features_modal').modal('toggle');
                        location.reload();
                    } else {
                        toastr.error(response.error);
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
    </script>
    <script>
        $('#check_all').on('click', function() {
            if ($(this).prop('checked')) {
                $('.feature_id').prop('checked', true);
            } else {
                $('.feature_id').prop('checked', false);
            }
        });
    </script>
@endpush
