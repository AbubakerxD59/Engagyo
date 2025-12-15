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
                                                <input type="number" step="0.01" class="form-control" name="price" id="price"
                                                    value="{{ old('price', $package->price) }}"
                                                    placeholder="Enter package price" required>
                                                @error('price')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="monthly_price" class="form-label">Monthly Price</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="number" step="0.01" class="form-control" name="monthly_price" id="monthly_price"
                                                    value="{{ old('monthly_price', $package->monthly_price) }}" placeholder="Enter monthly price (optional)">
                                                @error('monthly_price')
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
                                                    value="{{ old('trial_days', $package->trial_days ?? 0) }}" placeholder="Enter trial days" min="0">
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
                                                    value="{{ old('sort_order', $package->sort_order ?? 0) }}" placeholder="Enter sort order" min="0">
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
                                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}>
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
                                        <div class="row">
                                            @php
                                                $packageFeatures = $package->features->pluck('pivot.limit_value', 'id')->toArray();
                                                $packageFeatureEnabled = $package->features->pluck('pivot.is_enabled', 'id')->toArray();
                                            @endphp
                                            @foreach ($features as $feature)
                                                @php
                                                    $isChecked = isset($packageFeatureEnabled[$feature->id]) && $packageFeatureEnabled[$feature->id];
                                                    $limitValue = $packageFeatures[$feature->id] ?? ($feature->default_value ?? '');
                                                    $showLimitInput = in_array($feature->type, ['numeric', 'unlimited']) && $isChecked;
                                                @endphp
                                                <div class="col-md-6 mb-3">
                                                    <div class="card feature-card {{ $isChecked ? 'border-primary' : '' }}"
                                                        style="border: 2px solid {{ $isChecked ? '#007bff' : '#dee2e6' }}; transition: all 0.3s;">
                                                        <div class="card-body p-3">
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input feature-checkbox" type="checkbox"
                                                                    name="features[{{ $feature->id }}][enabled]"
                                                                    id="feature_{{ $feature->id }}"
                                                                    value="1" {{ $isChecked ? 'checked' : '' }}
                                                                    data-feature-id="{{ $feature->id }}"
                                                                    data-feature-type="{{ $feature->type }}">
                                                                <label class="form-check-label font-weight-bold" for="feature_{{ $feature->id }}"
                                                                    style="cursor: pointer; font-size: 1.05rem;">
                                                                    {{ $feature->name }}
                                                                </label>
                                                            </div>
                                                            @if ($feature->description)
                                                                <p class="text-muted mb-2" style="font-size: 0.875rem; margin-left: 1.5rem;">
                                                                    {{ $feature->description }}
                                                                </p>
                                                            @endif
                                                            @if (in_array($feature->type, ['numeric', 'unlimited']))
                                                                <div class="feature-limit-input" style="margin-left: 1.5rem; {{ !$isChecked ? 'display:none;' : '' }}">
                                                                    <label class="form-label small">Limit:</label>
                                                                    <input type="number" class="form-control form-control-sm"
                                                                        name="features[{{ $feature->id }}][limit]"
                                                                        id="feature_limit_{{ $feature->id }}"
                                                                        value="{{ $isChecked ? $limitValue : '' }}"
                                                                        placeholder="{{ $feature->type == 'unlimited' ? 'Unlimited' : 'Enter limit' }}"
                                                                        min="0"
                                                                        {{ $feature->type == 'unlimited' ? 'readonly' : '' }}>
                                                                    @if ($feature->type == 'unlimited')
                                                                        <small class="text-muted">Unlimited</small>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if ($features->isEmpty())
                                                <div class="col-12">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> No features available. Please create features first.
                                                    </div>
                                                </div>
                                            @endif
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
            $('.feature-checkbox').on('change', function() {
                var checkbox = $(this);
                var featureId = checkbox.data('feature-id');
                var featureType = checkbox.data('feature-type');
                var limitInput = $('#feature_limit_' + featureId);
                var limitContainer = limitInput.closest('.feature-limit-input');
                var featureCard = checkbox.closest('.feature-card');

                if (checkbox.is(':checked')) {
                    limitContainer.slideDown();
                    featureCard.removeClass('border-secondary').addClass('border-primary');
                    
                    // Set default value if empty and feature type is numeric
                    if (featureType === 'numeric' && !limitInput.val()) {
                        var defaultVal = limitInput.attr('data-default-value') || '';
                        limitInput.val(defaultVal);
                    } else if (featureType === 'unlimited') {
                        limitInput.val('');
                    }
                } else {
                    limitContainer.slideUp();
                    featureCard.removeClass('border-primary').addClass('border-secondary');
                    limitInput.val('');
                }
            });

            // Trigger change on page load to set initial state
            $('.feature-checkbox').each(function() {
                if ($(this).is(':checked')) {
                    $(this).trigger('change');
                }
            });
        });
    </script>
    <style>
        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .feature-card.border-primary {
            background-color: #f0f8ff;
        }

        .feature-checkbox {
            cursor: pointer;
        }

        .feature-limit-input {
            transition: all 0.3s ease;
        }
    </style>
@endpush
