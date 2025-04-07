@extends('admin.layouts.secure')
@section('page_title', $user->full_name)
@section('page_content')
    @can('edit_user')
        <div class="page-content">
            <form method="POST" action="{{ route('users.update_info', $user->id) }}" class="form-horizontal"
                enctype="multipart/form-data">
                @csrf
                <div class="content-header clearfix">
                    <h1 class="float-left"> USER DETAIL
                        <small>
                            <i class="fas fa-arrow-circle-left"></i>
                            <a href="{{ route('users.index') }}">back to Users list</a>
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
                                                    <label for="full_name"
                                                        class="col-sm-3 col-form-label">{{ __('users.full_name') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="full_name" name="full_name"
                                                            class="form-control"
                                                            placeholder="{{ __('users.full_name_placeholder') }}"
                                                            value="{{ old('full_name', @$user->full_name) }}" required />
                                                        @error('full_name')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="email"
                                                        class="col-sm-3 col-form-label">{{ __('users.email') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="email" id="email" name="email" class="form-control"
                                                            placeholder="{{ __('users.email_placeholder') }}"
                                                            value="{{ old('email', @$user->email) }}" required
                                                            autocomplete="off" />
                                                        @error('email')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="username"
                                                        class="col-sm-3 col-form-label">{{ __('users.username') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="username" name="username"
                                                            class="form-control"
                                                            placeholder="{{ __('users.username_placeholder') }}"
                                                            value="{{ old('username', @$user->username) }}" required
                                                            autocomplete="off" />
                                                        @error('username')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="password"
                                                        class="col-sm-3 col-form-label">{{ __('users.password') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="password" id="password" name="password"
                                                            class="form-control"
                                                            placeholder="{{ __('users.password_placeholder') }}"
                                                            autocomplete="off" />
                                                        @error('address')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="phone_number"
                                                        class="col-sm-3 col-form-label">{{ __('users.phone_number') }}</label>
                                                    <div class="col-sm-9">
                                                        <input type="number" id="phone_number" name="phone_number"
                                                            class="form-control"
                                                            placeholder="{{ __('users.phone_number_placeholder') }}"
                                                            value="{{ old('phone_number', @$user->phone_number) }}" required />
                                                        @error('phone_number')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="status"
                                                        class="col-sm-3 col-form-label">{{ __('users.status') }}</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-control" name="status" id="status">
                                                            <option value="">Select Status</option>
                                                            <option value="1"
                                                                {{ old('status', $user->status) == '1' ? 'selected' : '' }}>
                                                                Active</option>
                                                            <option value="0"
                                                                {{ old('status', $user->status) == '0' ? 'selected' : '' }}>In
                                                                Active</option>
                                                        </select>
                                                    </div>
                                                    @error('profile_pic')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group row">
                                                    <label for="profile_pic"
                                                        class="col-sm-3 col-form-label">{{ __('users.profile_pic') }}</label>
                                                    <div class="col-sm-9 dropzone" id="my-dropzone">
                                                        <input type="hidden" name="profile_pic" id="profile_pic">
                                                        @error('profile_pic')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 text-right">
                                            <button type="submit" class="btn btn-outline-success"
                                                onClick="processDropzone()">{{ __('users.btn_submit_text') }}</button>
                                            <a href="{{ route('users.index') }}"
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
@push('styles')
    <!-- Dropzone CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
@endpush
@push('scripts')
    <!-- dropzone -->
    <!-- jQuery (required by Dropzone) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Dropzone JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script>
        Dropzone.autoDiscover = false;
        // Initialize Dropzone
        var dropzone = new Dropzone('#my-dropzone', {
            url: "{{ route('users.upload_image') }}",
            method: 'post',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
            },
            paramName: "file", // The name that will be used to transfer the file
            parallelUploads: 4,
            maxFiles: 1,
            acceptedFiles: "image/*", // Allow only images
            autoProcessQueue: true,
            addRemoveLinks: true,
            success: function(response, file) {
                if (file != '' && file != null) {
                    $('#profile_pic').val(file);
                }
            }
        });

        function processDropzone() {
            if (confirm('Do you wish to submit?')) {
                dropzone.processQueue();
            }
        }
        dropzone.on("queuecomplete", function(response) {
            // Display alert when all files are uploaded
            toastr.success('Gallery uploaded successfully!');

        });
    </script>
@endpush
