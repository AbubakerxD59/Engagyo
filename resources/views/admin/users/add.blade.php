@extends('admin.layouts.secure')
@section('page_title', 'Users')
@section('page_content')
    @can('add_user')
        <div class="page-content">
            <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="content-header clearfix">
                    <h1 class="float-left"> {{ __('users.add_page_heading') }}
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
                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="first_name" id="first_name"
                                                    value="{{ old('first_name') }}" placeholder="Enter first name" required>
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
                                                    value="{{ old('last_name') }}" placeholder="Enter last name" required>
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
                                                    value="{{ old('email') }}" placeholder="Enter user email">
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
                                                <input type="password" class="form-control" name="password" id="password"
                                                    placeholder="Enter user password">
                                                @error('password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>


                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="password_confirmation" class="form-label">Confirm
                                                    Password</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="password_confirmation" class="form-control"
                                                    name="password_confirmation" id="password_confirmation"
                                                    placeholder="Confirm password">
                                                @error('password_confirmation')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

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
                                                                <option value="{{ $role->id }}">
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
                                                            <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                                                {{ $package->name }} - ${{ number_format($package->price, 2) }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('package_id')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row" id="full_access_row" style="display: none;">
                                            <div class="col-md-3">
                                                <label for="full_access" class="form-label">Access Type</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="full_access" id="full_access" value="1" {{ old('full_access') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="full_access">
                                                        <strong>Full Access</strong> (Never expires - for demo/admin users)
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    Enable this for users who should have unlimited access without expiration (e.g., demo users, admin accounts)
                                                </small>
                                                @error('full_access')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-12 text-right">
                                            <button type="submit"
                                                class="btn btn-outline-success">{{ __('users.btn_submit_text') }}</button>
                                            <a href="{{ route('admin.users.index') }}"
                                                class="btn btn-outline-dark">{{ __('users.btn_cancel_text') }}</a>
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
@endpush
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
