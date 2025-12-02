@extends('user.layout.main')
@section('title', 'Settings')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    {{-- Profile Picture Card --}}
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="profile-pic-wrapper mb-3">
                                    <div class="profile-pic-container">
                                        @if ($user->profile_pic)
                                            <img src="{{ asset($user->profile_pic) }}" alt="Profile Picture"
                                                class="profile-pic" id="profilePicPreview">
                                        @else
                                            <img src="{{ asset('assets/img/noimage.png') }}" alt="Profile Picture"
                                                class="profile-pic" id="profilePicPreview">
                                        @endif
                                        <div class="profile-pic-overlay" id="profilePicOverlay">
                                            <i class="fas fa-camera"></i>
                                            <span>Change</span>
                                        </div>
                                    </div>
                                    <input type="file" id="profilePicInput" accept="image/*" style="display: none;">
                                </div>
                                <h4 class="mb-1">{{ $user->full_name }}</h4>
                                <p class="text-muted mb-3">{{ $user->email }}</p>
                                @if ($user->profile_pic)
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="removeProfilePicBtn">
                                        <i class="fas fa-trash mr-1"></i> Remove Photo
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Change Password Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-lock mr-2"></i>Change Password
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="passwordForm">
                                    <div class="form-group">
                                        <label>Current Password</label>
                                        <input type="password" class="form-control" name="current_password"
                                            placeholder="Enter current password">
                                    </div>
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" class="form-control" name="password"
                                            placeholder="Enter new password">
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" class="form-control" name="password_confirmation"
                                            placeholder="Confirm new password">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block" id="updatePasswordBtn">
                                        <i class="fas fa-key mr-1"></i> Update Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Profile Information Card --}}
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user mr-2"></i>Profile Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="profileForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name"
                                                    value="{{ $user->first_name }}" placeholder="Enter first name">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name"
                                                    value="{{ $user->last_name }}" placeholder="Enter last name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" class="form-control bg-light" value="{{ $user->email }}"
                                            readonly disabled>
                                        <small class="form-text text-muted">Email cannot be changed</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Phone Number</label>
                                                <input type="text" class="form-control" name="phone_number"
                                                    value="{{ $user->phone_number }}" placeholder="Enter phone number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>City</label>
                                                <input type="text" class="form-control" name="city"
                                                    value="{{ $user->city }}" placeholder="Enter city">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Country</label>
                                                <input type="text" class="form-control" name="country"
                                                    value="{{ $user->country }}" placeholder="Enter country">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Address</label>
                                        <textarea class="form-control" name="address" rows="2" placeholder="Enter address">{{ $user->address }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Timezone</label>
                                        <select class="form-control select2" name="timezone_id">
                                            <option value="">Select Timezone</option>
                                            @foreach ($timezones as $timezone)
                                                <option value="{{ $timezone->id }}"
                                                    {{ $user->timezone_id == $timezone->id ? 'selected' : '' }}>
                                                    {{ $timezone->name }} ({{ $timezone->offset }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary" id="updateProfileBtn">
                                            <i class="fas fa-save mr-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .profile-pic-wrapper {
            display: inline-block;
        }

        .profile-pic-container {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            margin: 0 auto;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-pic-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            color: white;
        }

        .profile-pic-container:hover .profile-pic-overlay {
            opacity: 1;
        }

        .profile-pic-overlay i {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .profile-pic-overlay span {
            font-size: 12px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }

        .card-header h5 {
            font-size: 16px;
            font-weight: 600;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();

            // Profile picture click
            $('#profilePicOverlay, .profile-pic-container').on('click', function() {
                $('#profilePicInput').click();
            });

            // Profile picture change
            $('#profilePicInput').on('change', function() {
                var file = this.files[0];
                if (file) {
                    // Validate file type
                    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        toastr.error('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
                        return;
                    }

                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        toastr.error('Image size must not exceed 5MB');
                        return;
                    }

                    // Preview image
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#profilePicPreview').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);

                    // Upload image
                    var formData = new FormData();
                    formData.append('profile_pic', file);

                    $.ajax({
                        url: "{{ route('panel.settings.updateProfilePic') }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                // Update header image
                                $('.main-header .userimg').attr('src', response.data
                                    .profile_pic);
                                // Show remove button
                                if (!$('#removeProfilePicBtn').length) {
                                    location.reload();
                                }
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Failed to upload image');
                        }
                    });
                }
            });

            // Remove profile picture
            $('#removeProfilePicBtn').on('click', function() {
                if (!confirm('Are you sure you want to remove your profile picture?')) {
                    return;
                }

                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Removing...');

                $.ajax({
                    url: "{{ route('panel.settings.removeProfilePic') }}",
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to remove image');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-trash mr-1"></i> Remove Photo');
                    }
                });
            });

            // Update profile form
            $('#profileForm').on('submit', function(e) {
                e.preventDefault();
                var btn = $('#updateProfileBtn');
                btn.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

                $.ajax({
                    url: "{{ route('panel.settings.update') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Update name in header if changed
                            if (response.data.user.full_name) {
                                $('.main-header .user-panel .info span').text(response.data
                                    .user.full_name);
                            }
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to update profile');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-save mr-1"></i> Save Changes');
                    }
                });
            });

            // Update password form
            $('#passwordForm').on('submit', function(e) {
                e.preventDefault();
                var btn = $('#updatePasswordBtn');
                btn.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Updating...');

                $.ajax({
                    url: "{{ route('panel.settings.updatePassword') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#passwordForm')[0].reset();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to update password');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-key mr-1"></i> Update Password');
                    }
                });
            });
        });
    </script>
@endpush
