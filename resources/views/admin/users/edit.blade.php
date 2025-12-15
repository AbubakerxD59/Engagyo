@extends('admin.layouts.secure')
@section('page_title', $user->full_name)
@section('page_content')
    @can('edit_user')
        <div class="page-content">
            <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="form-horizontal">
                @csrf
                @method('PUT')
                <div class="content-header clearfix">
                    <h1 class="float-left"> Edit User
                        <small>
                            <i class="fas fa-arrow-circle-left"></i>
                            <a href="{{ route('admin.users.index') }}">back to Users list</a>
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
                                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="first_name" id="first_name"
                                                    value="{{ old('first_name', $user->first_name) }}"
                                                    placeholder="Enter first name" required>
                                                @error('first_name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="last_name" id="last_name"
                                                    value="{{ old('last_name', $user->last_name) }}"
                                                    placeholder="Enter last name" required>
                                                @error('last_name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="email" class="form-label">Email</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="email" class="form-control" name="email" id="email"
                                                    value="{{ old('email', $user->email) }}" placeholder="Enter user email">
                                                @error('email')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="password" class="form-label">Password</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="password" class="form-control" name="password" id="password">
                                                @error('password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <?php
                                        $roleName = !empty($user->roles()->first()) ? $user->roles()->first()->id : '0';
                                        ?>
                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="role" class="form-label">Role</label>
                                            </div>
                                            <div class="col-md-9">
                                                <select name="role" id="role" class="form-control">
                                                    <option value="">Select Role</option>
                                                    @if (count($roles) > 0)
                                                        @foreach ($roles as $role)
                                                            @if (!in_array($role->name, ['Super Admin']))
                                                                <option value="{{ $role->id }}"
                                                                    {{ old('role', $roleName) == $role->id ? 'selected' : '' }}>
                                                                    {{ $role->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('role')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="package_id" class="form-label">Package</label>
                                            </div>
                                            <div class="col-md-9">
                                                <select name="package_id" id="package_id" class="form-control">
                                                    <option value="">Select Package (Optional)</option>
                                                    @if (isset($packages) && count($packages) > 0)
                                                        @foreach ($packages as $package)
                                                            <option value="{{ $package->id }}"
                                                                {{ old('package_id', $user->package_id) == $package->id ? 'selected' : '' }}>
                                                                {{ $package->name }} -
                                                                ${{ number_format($package->price, 2) }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('package_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                                @php
                                                    $activeUserPackage = $user->activeUserPackage;
                                                @endphp
                                                @if ($activeUserPackage && $activeUserPackage->package)
                                                    <small class="form-text text-muted">
                                                        Current Package: {{ $activeUserPackage->package->name }}
                                                        @if ($activeUserPackage->expires_at)
                                                            | Expires:
                                                            {{ $activeUserPackage->expires_at->format('Y-m-d H:i') }}
                                                        @else
                                                            | <strong>Full Access (Never Expires)</strong>
                                                        @endif
                                                    </small>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="form-group row" id="full_access_row"
                                            style="{{ old('package_id', $user->package_id) ? '' : 'display: none;' }}">
                                            <div class="col-md-3">
                                                <label for="full_access" class="form-label">Access Type</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="full_access"
                                                        id="full_access" value="1"
                                                        {{ old('full_access') || ($activeUserPackage && !$activeUserPackage->expires_at) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="full_access">
                                                        <strong>Full Access</strong> (Never expires - for demo/admin users)
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    Enable this for users who should have unlimited access without expiration
                                                    (e.g., demo users, admin accounts)
                                                </small>
                                                @error('full_access')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="active" class="form-label">Is Active</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-check">
                                                    <input type="hidden" name="active" value="0">
                                                    <input type="checkbox" id="activeCheckbox" name="active"
                                                        class="form-check-input" value="1"
                                                        {{ $user->status ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="active">Yes</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Features Usage Section --}}
                        @php $featuresWithUsage = $user->getFeaturesWithUsage(); @endphp
                        @if ($featuresWithUsage && $featuresWithUsage->count() > 0)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <i class="fas fa-star"></i>
                                                Features & Usage
                                            </div>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                                    title="Collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Feature</th>
                                                            <th>Description</th>
                                                            <th>Limit</th>
                                                            <th>Used</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($featuresWithUsage as $feature)
                                                            <tr>
                                                                <td><strong>{{ $feature['name'] }}</strong></td>
                                                                <td>{{ $feature['description'] ?? '-' }}</td>
                                                                <td>
                                                                    @if ($feature['is_unlimited'])
                                                                        <span class="badge badge-info">Unlimited</span>
                                                                    @elseif($feature['is_boolean'])
                                                                        <span
                                                                            class="badge badge-{{ $feature['usage'] ? 'success' : 'secondary' }}">
                                                                            {{ $feature['usage'] ? 'Enabled' : 'Disabled' }}
                                                                        </span>
                                                                    @else
                                                                        {{ $feature['limit'] ?? 'N/A' }}
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($feature['is_boolean'])
                                                                        <span
                                                                            class="badge badge-{{ $feature['usage'] ? 'success' : 'secondary' }}">
                                                                            {{ $feature['usage'] ? 'Yes' : 'No' }}
                                                                        </span>
                                                                    @else
                                                                        <strong>{{ $feature['usage'] }}</strong>
                                                                        @if (!$feature['is_unlimited'] && $feature['limit'])
                                                                            / {{ $feature['limit'] }}
                                                                        @endif
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($feature['is_unlimited'] || $feature['is_boolean'])
                                                                        <span class="badge badge-success">Active</span>
                                                                    @elseif($feature['is_over_limit'])
                                                                        <span class="badge badge-danger">Over Limit</span>
                                                                    @elseif($feature['usage_percentage'] >= 80)
                                                                        <span class="badge badge-warning">Near Limit</span>
                                                                    @else
                                                                        <span class="badge badge-success">Active</span>
                                                                    @endif
                                                                    @if (!$feature['is_unlimited'] && !$feature['is_boolean'] && $feature['limit'])
                                                                        <div class="progress mt-1" style="height: 5px;">
                                                                            <div class="progress-bar 
                                                                            {{ $feature['is_over_limit'] ? 'bg-danger' : ($feature['usage_percentage'] >= 80 ? 'bg-warning' : 'bg-success') }}"
                                                                                role="progressbar"
                                                                                style="width: {{ min($feature['usage_percentage'], 100) }}%">
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            </form>
        </div>
    @endcan
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#package_id').on('change', function() {
                var packageId = $(this).val();
                if (packageId) {
                    $('#full_access_row').slideDown();
                } else {
                    $('#full_access_row').slideUp();
                    $('#full_access').prop('checked', false);
                }
            });

            // Trigger on page load if package is already selected
            if ($('#package_id').val()) {
                $('#full_access_row').show();
            }
        });
    </script>
@endpush
