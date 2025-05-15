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
