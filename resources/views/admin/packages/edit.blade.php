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
                                                <label for="description" class="form-label">Description</label>
                                            </div>
                                            <div class="col-md-9">
                                                <textarea class="form-control" name="description" id="description" rows="3"
                                                    placeholder="Enter package description">{{ old('description', $package->description) }}</textarea>
                                                @error('description')
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
                                                    placeholder="Enter package duration">
                                                @error('duration')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-5">
                                                <select class="form-control" name="date_type" id="date_type">
                                                    <option value="day"
                                                        {{ $package->date_type == 'day' ? 'selected' : '' }}>
                                                        Day(s)</option>
                                                    <option value="month"
                                                        {{ $package->date_type == 'month' ? 'selected' : '' }}>Month(s)
                                                    </option>
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
                                                <input type="number" step="0.01" class="form-control" name="price"
                                                    id="price" value="{{ old('price', $package->price) }}"
                                                    placeholder="Enter package price" required>
                                                @error('price')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>


                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="trial_days" class="form-label">Trial Days</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="number" class="form-control" name="trial_days" id="trial_days"
                                                    value="{{ old('trial_days', $package->trial_days ?? 0) }}"
                                                    placeholder="Enter trial days" min="0">
                                                @error('trial_days')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="sort_order" class="form-label">Sort Order</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="number" class="form-control" name="sort_order" id="sort_order"
                                                    value="{{ old('sort_order', $package->sort_order ?? 0) }}"
                                                    placeholder="Enter sort order" min="0">
                                                @error('sort_order')
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
                                                    <input class="form-check-input" type="checkbox" name="is_active"
                                                        id="is_active" value="1"
                                                        {{ old('i s_active', $package->is_active ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_active">
                                                        Active
                                                    </label>
                                                </div>
                                                @error('is_active')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="is_lifetime" class="form-label">Lifetime</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_lifetime"
                                                        id="is_lifetime" value="1"
                                                        {{ old('is_lifetime', $package->is_lifetime ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_lifetime">
                                                        Yes
                                                    </label>
                                                </div>
                                                @error('is_lifetime')
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
                            </div>
                        </div>

                        {{-- Features Section --}}
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <i class="fas fa-star"></i>
                                            Features
                                        </div>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                                title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @php
                                            $packageFeatures = $package->features
                                                ->pluck('pivot.limit_value', 'id')
                                                ->toArray();
                                            $packageFeatureEnabled = $package->features
                                                ->pluck('pivot.is_enabled', 'id')
                                                ->toArray();
                                            $packageFeatureUnlimited = $package->features
                                                ->pluck('pivot.is_unlimited', 'id')
                                                ->toArray();
                                        @endphp
                                        
                                        @if ($features->isEmpty())
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No features available. Please create features first.
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th width="50">
                                                                <input type="checkbox" id="selectAllFeatures" title="Select All">
                                                            </th>
                                                            <th>Feature</th>
                                                            <th>Type</th>
                                                            <th>Status</th>
                                                            <th>Limit Configuration</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($features as $feature)
                                                            @php
                                                                $isChecked = isset($packageFeatureEnabled[$feature->id]) &&
                                                                    $packageFeatureEnabled[$feature->id];
                                                                $isUnlimited = isset($packageFeatureUnlimited[$feature->id]) &&
                                                                    $packageFeatureUnlimited[$feature->id];
                                                                $limitValue = $packageFeatures[$feature->id] ??
                                                                    ($feature->default_value ?? '');
                                                                $isBoolean = $feature->type === 'boolean';
                                                                $isNumeric = $feature->type === 'numeric';
                                                                $isUnlimitedType = $feature->type === 'unlimited';
                                                            @endphp
                                                            <tr class="feature-row {{ $isChecked ? 'table-primary' : '' }}">
                                                                <td>
                                                                    <input class="form-check-input feature-checkbox"
                                                                        type="checkbox"
                                                                        name="features[{{ $feature->id }}][enabled]"
                                                                        id="feature_{{ $feature->id }}" value="1"
                                                                        {{ $isChecked ? 'checked' : '' }}
                                                                        data-feature-id="{{ $feature->id }}"
                                                                        data-feature-type="{{ $feature->type }}">
                                                                </td>
                                                                <td>
                                                                    <label class="mb-0 font-weight-bold" for="feature_{{ $feature->id }}" style="cursor: pointer;">
                                                                        {{ $feature->name }}
                                                                    </label>
                                                                    @if ($feature->key)
                                                                        <br><small class="text-muted">{{ $feature->key }}</small>
                                                                    @endif
                                                                    @if ($feature->description)
                                                                        <br><small class="text-muted">{{ $feature->description }}</small>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-{{ $isBoolean ? 'info' : ($isNumeric ? 'warning' : 'success') }}">
                                                                        {{ ucfirst($feature->type) }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-{{ $isChecked ? 'success' : 'secondary' }}">
                                                                        {{ $isChecked ? 'Enabled' : 'Disabled' }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    @if ($isBoolean)
                                                                        <span class="text-muted">N/A</span>
                                                                    @elseif ($isUnlimitedType)
                                                                        <span class="badge badge-info">Unlimited</span>
                                                                    @elseif ($isNumeric)
                                                                        @if ($isChecked)
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input unlimited-checkbox"
                                                                                        type="checkbox"
                                                                                        name="features[{{ $feature->id }}][unlimited]"
                                                                                        id="feature_unlimited_{{ $feature->id }}"
                                                                                        value="1"
                                                                                        data-feature-id="{{ $feature->id }}"
                                                                                        {{ $isUnlimited ? 'checked' : '' }}>
                                                                                    <label class="form-check-label small" for="feature_unlimited_{{ $feature->id }}">
                                                                                        Unlimited
                                                                                    </label>
                                                                                </div>
                                                                                <div class="feature-limit-input" style="{{ $isUnlimited ? 'display:none;' : '' }}">
                                                                                    <input type="number"
                                                                                        class="form-control form-control-sm d-inline-block"
                                                                                        style="width: 100px;"
                                                                                        name="features[{{ $feature->id }}][limit]"
                                                                                        id="feature_limit_{{ $feature->id }}"
                                                                                        value="{{ !$isUnlimited ? $limitValue : ($feature->default_value ?? '') }}"
                                                                                        placeholder="Limit" min="0">
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="d-flex align-items-center gap-2" style="display: none;">
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input unlimited-checkbox"
                                                                                        type="checkbox"
                                                                                        name="features[{{ $feature->id }}][unlimited]"
                                                                                        id="feature_unlimited_{{ $feature->id }}"
                                                                                        value="1"
                                                                                        data-feature-id="{{ $feature->id }}">
                                                                                    <label class="form-check-label small" for="feature_unlimited_{{ $feature->id }}">
                                                                                        Unlimited
                                                                                    </label>
                                                                                </div>
                                                                                <div class="feature-limit-input">
                                                                                    <input type="number"
                                                                                        class="form-control form-control-sm d-inline-block"
                                                                                        style="width: 100px;"
                                                                                        name="features[{{ $feature->id }}][limit]"
                                                                                        id="feature_limit_{{ $feature->id }}"
                                                                                        value=""
                                                                                        placeholder="Limit" min="0"
                                                                                        data-default-value="{{ $feature->default_value }}">
                                                                                </div>
                                                                            </div>
                                                                            <span class="text-muted">Enable to configure</span>
                                                                        @endif
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
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

        // Feature checkbox toggle
        $(document).ready(function() {
            // Select all features checkbox
            $('#selectAllFeatures').on('change', function() {
                $('.feature-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
            });

            // Feature checkbox toggle
            $('.feature-checkbox').on('change', function() {
                var checkbox = $(this);
                var featureId = checkbox.data('feature-id');
                var featureType = checkbox.data('feature-type');
                var featureRow = checkbox.closest('.feature-row');
                var limitInput = $('#feature_limit_' + featureId);
                var limitInputContainer = limitInput.closest('.feature-limit-input');
                var unlimitedCheckbox = $('#feature_unlimited_' + featureId);

                if (checkbox.is(':checked')) {
                    featureRow.addClass('table-primary');
                    
                    // Show limit controls for numeric features
                    if (featureType === 'numeric') {
                        var limitControlsContainer = limitInputContainer.closest('.d-flex');
                        var statusText = featureRow.find('td:nth-child(5) .text-muted');
                        
                        if (limitControlsContainer.length) {
                            statusText.hide();
                            limitControlsContainer.show();
                            if (unlimitedCheckbox.is(':checked')) {
                                limitInputContainer.hide();
                            } else {
                                limitInputContainer.show();
                                if (!limitInput.val()) {
                                    var defaultVal = limitInput.attr('data-default-value') || '';
                                    limitInput.val(defaultVal);
                                }
                            }
                        }
                    }
                } else {
                    featureRow.removeClass('table-primary');
                    limitInput.val('');
                    if (unlimitedCheckbox.length) {
                        unlimitedCheckbox.prop('checked', false);
                        var limitControlsContainer = limitInputContainer.closest('.d-flex');
                        var statusText = featureRow.find('td:nth-child(5) .text-muted');
                        if (limitControlsContainer.length) {
                            limitControlsContainer.hide();
                            statusText.show();
                        }
                    }
                    limitInputContainer.hide();
                }
                
                // Update select all checkbox state
                updateSelectAllState();
            });

            // Unlimited checkbox toggle
            $('.unlimited-checkbox').on('change', function() {
                var unlimitedCheckbox = $(this);
                var featureId = unlimitedCheckbox.data('feature-id');
                var limitInput = $('#feature_limit_' + featureId);
                var limitInputContainer = limitInput.closest('.feature-limit-input');

                if (unlimitedCheckbox.is(':checked')) {
                    limitInputContainer.slideUp();
                    limitInput.val('');
                } else {
                    limitInputContainer.slideDown();
                }
            });

            // Update select all checkbox state
            function updateSelectAllState() {
                var totalCheckboxes = $('.feature-checkbox').length;
                var checkedCheckboxes = $('.feature-checkbox:checked').length;
                $('#selectAllFeatures').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
            }

            // Trigger change on page load to set initial state
            $('.feature-checkbox').each(function() {
                if ($(this).is(':checked')) {
                    $(this).trigger('change');
                }
            });
            
            updateSelectAllState();
        });
    </script>
    <style>
        .feature-row {
            transition: all 0.2s ease;
        }

        .feature-row:hover {
            background-color: #f8f9fa;
        }

        .feature-row.table-primary {
            background-color: #cfe2ff !important;
        }

        .feature-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        .feature-limit-input {
            transition: all 0.3s ease;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        #selectAllFeatures {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
    </style>
@endpush
