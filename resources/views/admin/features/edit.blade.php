@extends('admin.layouts.secure')
@section('page_title', 'Edit Feature')
@section('page_content')
    @can('edit_feature')
        <div class="page-content">
            <form method="POST" action="{{ route('admin.features.update', $feature->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="content-header clearfix">
                    <h1 class="float-left"> Edit Feature
                        <small>
                            <i class="fas fa-arrow-circle-left"></i>
                            <a href="{{ route('admin.features.index') }}">back to Features list</a>
                        </small>
                    </h1>
                    <div class="float-right">
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            <i class="far fa-save"></i>
                            Update
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
                                                <label for="key" class="form-label">Key <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="key" id="key"
                                                    value="{{ old('key', $feature->key) }}" placeholder="e.g., facebook_accounts" required>
                                                <small class="form-text text-muted">Unique identifier for the feature (use lowercase with underscores)</small>
                                                @error('key')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="name" id="name"
                                                    value="{{ old('name', $feature->name) }}" placeholder="Enter feature name" required>
                                                @error('name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="col-md-9">
                                                <select class="form-control" name="type" id="type" required>
                                                    <option value="boolean" {{ old('type', $feature->type) == 'boolean' ? 'selected' : '' }}>Boolean</option>
                                                    <option value="numeric" {{ old('type', $feature->type) == 'numeric' ? 'selected' : '' }}>Numeric</option>
                                                    <option value="unlimited" {{ old('type', $feature->type) == 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                                                </select>
                                                <small class="form-text text-muted">Boolean: enabled/disabled, Numeric: limited quantity, Unlimited: no limit</small>
                                                @error('type')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row" id="default_value_row">
                                            <div class="col-md-3">
                                                <label for="default_value" class="form-label">Default Value</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="number" class="form-control" name="default_value" id="default_value"
                                                    value="{{ old('default_value', $feature->default_value) }}" placeholder="Enter default value">
                                                <small class="form-text text-muted">Default value for this feature (0 for boolean false, number for numeric)</small>
                                                @error('default_value')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="description" class="form-label">Description</label>
                                            </div>
                                            <div class="col-md-9">
                                                <textarea class="form-control" name="description" id="description" rows="3"
                                                    placeholder="Enter feature description">{{ old('description', $feature->description) }}</textarea>
                                                @error('description')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="is_active" class="form-label">Status</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                                        {{ old('is_active', $feature->is_active) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_active">
                                                        Active
                                                    </label>
                                                </div>
                                                @error('is_active')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
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
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#type').on('change', function() {
                var type = $(this).val();
                if (type === 'unlimited') {
                    $('#default_value_row').hide();
                    $('#default_value').val('');
                } else {
                    $('#default_value_row').show();
                }
            });
            $('#type').trigger('change');
        });
    </script>
@endpush

