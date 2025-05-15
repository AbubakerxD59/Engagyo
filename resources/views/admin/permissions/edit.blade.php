@extends('admin.layouts.secure')
@section('page_title', 'Update Permission')
@section('page_content')
    @can('edit_permission')
        <div class="page-content">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>{{ __('permissions.edit_page_heading') }}</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a
                                        href="{{ route('admin.dashboard') }}">{{ __('permissions.page_breadcrumb_dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item active">{{ __('permissions.page_breadcrumb_list') }}</li>
                                <li class="breadcrumb-item active">{{ __('permissions.page_breadcrumb_edit') }}</li>
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
                                    <h3 class="card-title">{{ __('permissions.edit_page_sub_heading') }}</h3>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST"
                                    <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST"
                                        class="form-horizontal">
                                        @csrf
                                        @method('PUT')
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="label"
                                                        class="col-sm-3 col-form-label">{{ __('permissions.label') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="label" name="label" class="form-control"
                                                            placeholder="{{ __('permissions.label_placeholder') }}"
                                                            value="{{ old('label', $permission->label) }}" />
                                                        @error('label')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="route_name"
                                                        class="col-sm-3 col-form-label">{{ __('permissions.route_name') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="route_name" name="route_name"
                                                            class="form-control"
                                                            placeholder="{{ __('permissions.route_name_placeholder') }}"
                                                            value="{{ old('route_name', $permission->route_name) }}" />
                                                        @error('route_name')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="guard_name"
                                                        class="col-sm-3 col-form-label">{{ __('permissions.guard_name') }}</label>
                                                    <div class="col-sm-9">
                                                        <select id="guard_name" class="form-control" name="guard_name">
                                                            <option value="">{{ __('permissions.select_guard_name') }}
                                                            </option>
                                                            <option value="web"
                                                                {{ old('guard_name', $permission->guard_name) == 'web' ? 'selected' : '' }}>
                                                                {{ __('permissions.web') }}</option>
                                                        </select>
                                                        @error('guard_name')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="icon"
                                                        class="col-sm-3 col-form-label">{{ __('permissions.icon') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="icon" name="icon" class="form-control"
                                                            placeholder="{{ __('permissions.icon_placeholder') }}"
                                                            value="{{ old('icon', $permission->icon) }}" />
                                                        @error('icon')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="tool_tip"
                                                        class="col-sm-3 col-form-label">{{ __('permissions.tooltip') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="tool_tip" name="tool_tip"
                                                            class="form-control"
                                                            placeholder="{{ __('permissions.tooltip_placeholder') }}"
                                                            value="{{ old('tool_tip', $permission->tool_tip) }}" />
                                                        @error('tool_tip')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="role_id"
                                                        class="col-sm-3 col-form-label">{{ __('permissions.role') }}</label>
                                                    <div class="col-sm-9">
                                                        <select id="role_id" class="form-control" name="role_id">
                                                            <option value="">{{ __('permissions.select_role') }}</option>
                                                            @if (count($roles) > 0)
                                                                @foreach ($roles as $role)
                                                                    <option value="{{ $role->id }}"
                                                                        {{ old('role', $permission->role) == $role->id ? 'selected' : '' }}>
                                                                        {{ $role->name }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                        @error('role_id')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="parent_id"
                                                        class="col-sm-3 col-form-label">{{ __('permissions.parent') }}</label>
                                                    <div class="col-sm-9">
                                                        <select id="parent_id" class="form-control" name="parent_id">
                                                            <option value="">{{ __('permissions.select_parent') }}
                                                            </option>
                                                            @if (count($permissions) > 0)
                                                                @foreach ($permissions as $permm)
                                                                    <option value="{{ $permm->id }}"
                                                                        {{ old('parent_id', $permission->parent_id) == $permm->id ? 'selected' : '' }}>
                                                                        {{ $permm->label }}</option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                        @error('parent_id')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="parent_id" class="col-sm-3 col-form-label">&nbsp;</label>
                                                    <div class="col-sm-9">
                                                        <div class="icheck-primary">
                                                            <input type="checkbox" id="show_on_menu" name="show_on_menu" />
                                                            <label
                                                                for="show_on_menu">{{ __('permissions.show_on_menu') }}</label>
                                                        </div>
                                                        @error('show_on_menu')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 text-right">
                                                <button type="submit"
                                                    class="btn btn-outline-success">{{ __('permissions.btn_submit_text') }}</button>
                                                <a href="{{ route('admin.permissions.index') }}"
                                                <a href="{{ route('admin.permissions.index') }}"
                                                    class="btn btn-outline-dark">{{ __('permissions.btn_cancel_text') }}</a>
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
    <script type="text/javascript">
        document.addEventListener('livewire:init', () => {
            Livewire.on('updatePermissionError', (message) => {
                toastr.error(message);
            });
        });
    </script>
@endpush