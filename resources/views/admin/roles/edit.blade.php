@extends('admin.layouts.secure')
@section('page_title', 'Edit Role')
@section('page_content')
    @can('edit_role')
        <div class="page-content">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>{{ __('roles.edit_page_heading') }}</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a
                                        href="{{ route('admin.dashboard') }}">{{ __('roles.page_breadcrumb_dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a
                                        href="{{ route('admin.roles.index') }}">{{ __('roles.page_breadcrumb_list') }}</a></li>
                                <li class="breadcrumb-item active">{{ __('roles.page_breadcrumb_edit') }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('roles.edit_page_sub_heading') }}</h3>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST"
                                        class="form-horizontal">
                                        @csrf
                                        @method('PUT')
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="name"
                                                        class="col-sm-3 col-form-label">{{ __('roles.role_name') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="name" name="name" class="form-control"
                                                            placeholder="{{ __('roles.role_name_placeholder') }}" value="{{ old('name', $role->name) }}"/>
                                                        @error('name')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="guard_name"
                                                        class="col-sm-3 col-form-label">{{ __('roles.guard_name') }}</label>
                                                    <div class="col-sm-9">
                                                        <select id="guard_name" class="form-control" name="guard_name">
                                                            <option value="">{{ __('roles.select_guard_name') }}</option>
                                                            <option value="web" {{ old('guard_name', $role->guard_name) == 'web' ? 'selected' : '' }}>{{ __('roles.web') }}</option>
                                                        </select>
                                                        @error('guard_name')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 text-right">
                                                <button type="submit"
                                                    class="btn btn-outline-success">{{ __('roles.btn_submit_text') }}</button>
                                                <a href="{{ route('admin.roles.index') }}"
                                                    class="btn btn-outline-dark">{{ __('roles.btn_cancel_text') }}</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endcan
@endsection
@push('scripts')
    <script type="text/javascript"></script>
@endpush
