@extends('admin.layouts.secure')
@section('page_title', 'Add Role')
@section('page_content')
    @can('add_role')
        <div class="page-content">
            <form method="POST" action="{{ route('roles.store') }}" class="form-horizontal">
                @csrf
                <div class="content-header clearfix">
                    <h1 class="float-left"> Add Role
                        <small>
                            <i class="fas fa-arrow-circle-left"></i>
                            <a href="{{ route('roles.index') }}">back to Roles list</a>
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
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="name"
                                                        class="col-sm-3 col-form-label">{{ __('roles.role_name') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="name" name="name" class="form-control"
                                                            placeholder="{{ __('roles.role_name_placeholder') }}"
                                                            value="{{ old('name') }}" />
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
                                                        <select id="guard_name" name="guard_name" class="form-control">
                                                            <option value="">{{ __('roles.select_guard_name') }}</option>
                                                            <option value="web"
                                                                {{ old('guard_name') == 'web' ? 'selected' : '' }}>
                                                                {{ __('roles.web') }}</option>
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
                                                <a href="{{ route('roles.index') }}"
                                                    class="btn btn-outline-dark">{{ __('roles.btn_cancel_text') }}</a>
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
@endpush
