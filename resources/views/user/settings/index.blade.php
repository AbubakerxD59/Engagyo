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

                        {{-- Package Information Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-box mr-2"></i>Current Package
                                </h5>
                            </div>
                            <div class="card-body">
                                @if ($package)
                                    <div class="package-info">
                                        <h6 class="mb-2">{{ $package->name }}</h6>
                                        <div class="package-status mb-2">
                                            <span class="badge badge-{{ $packageStatus === 'Active' ? 'success' : ($packageStatus === 'Expired' ? 'danger' : ($packageStatus === 'Lifetime' ? 'info' : 'secondary')) }} package-status-badge"
                                                @if ($expiryDate && $packageStatus !== 'Lifetime')
                                                    data-toggle="tooltip" 
                                                    data-placement="top" 
                                                    title="Expiry date: {{ $expiryDate->format('jS M, Y') }}"
                                                @endif
                                                style="cursor: {{ $expiryDate && $packageStatus !== 'Lifetime' ? 'help' : 'default' }};">
                                                <i class="fas fa-{{ $packageStatus === 'Active' ? 'check-circle' : ($packageStatus === 'Expired' ? 'times-circle' : ($packageStatus === 'Lifetime' ? 'infinity' : 'question-circle')) }} mr-1"></i>
                                                {{ $packageStatus }}
                                            </span>
                                        </div>
                                        @if ($expiryDate && $packageStatus !== 'Lifetime')
                                            <p class="text-muted mb-0 small">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                Expires: {{ $expiryDate->format('jS M, Y') }}
                                                <span class="ml-1" data-toggle="tooltip" data-placement="top" title="Expiry date: {{ $expiryDate->format('jS M, Y') }}">
                                                    <i class="fas fa-info-circle"></i>
                                                </span>
                                            </p>
                                        @elseif ($packageStatus === 'Lifetime')
                                            <p class="text-muted mb-0 small">
                                                <i class="fas fa-infinity mr-1"></i>
                                                No expiration
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2"></i>
                                        <p class="mb-0">No active package</p>
                                    </div>
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

                        {{-- Features & Usage Card --}}
                        @if ($package && count($featuresWithUsage) > 0)
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list-check mr-2"></i>Available Features & Usage
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="features-list">
                                        @foreach ($featuresWithUsage as $feature)
                                            <div class="feature-item mb-3 pb-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">{{ $feature['name'] }}</h6>
                                                        @if (!empty($feature['description']))
                                                            <p class="text-muted small mb-2">{{ $feature['description'] }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="feature-usage">
                                                    @if ($feature['is_unlimited'])
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge badge-success mr-2">
                                                                <i class="fas fa-infinity mr-1"></i>Unlimited
                                                            </span>
                                                            <span class="text-muted small">Usage: {{ number_format($feature['usage']) }}</span>
                                                        </div>
                                                    @elseif ($feature['limit'] !== null)
                                                        <div class="usage-info">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small text-muted">
                                                                    {{ number_format($feature['usage']) }} / {{ number_format($feature['limit']) }}
                                                                </span>
                                                                <span class="small font-weight-bold {{ $feature['remaining'] === 0 ? 'text-danger' : ($feature['remaining'] !== null && $feature['remaining'] <= ($feature['limit'] * 0.2) ? 'text-warning' : 'text-success') }}">
                                                                    {{ $feature['remaining'] !== null ? $feature['remaining'] . ' remaining' : 'N/A' }}
                                                                </span>
                                                            </div>
                                                            <div class="progress" style="height: 8px;">
                                                                @php
                                                                    $usagePercentage = $feature['limit'] > 0 ? min(100, round(($feature['usage'] / $feature['limit']) * 100, 2)) : 0;
                                                                    $progressClass = $usagePercentage >= 100 ? 'bg-danger' : ($usagePercentage >= 80 ? 'bg-warning' : 'bg-success');
                                                                @endphp
                                                                <div class="progress-bar {{ $progressClass }}" role="progressbar" 
                                                                     style="width: {{ $usagePercentage }}%" 
                                                                     aria-valuenow="{{ $usagePercentage }}" 
                                                                     aria-valuemin="0" 
                                                                     aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="d-flex align-items-center">
                                                            <span class="text-muted small">Usage: {{ number_format($feature['usage']) }}</span>
                                                            <span class="badge badge-secondary ml-2">No limit</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
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

        .package-info h6 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .package-status-badge {
            font-size: 13px;
            padding: 6px 12px;
            cursor: help;
        }

        .feature-item:last-child {
            border-bottom: none !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        .feature-item h6 {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .usage-info {
            width: 100%;
        }

        .progress {
            border-radius: 4px;
            background-color: #e9ecef;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Profile picture click - only bind to container to avoid double triggering
            $('.profile-pic-container').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#profilePicInput').click();
            });

            // Profile picture change
            $('#profilePicInput').on('change', function() {
                var file = this.files[0];
                var $input = $(this);
                
                if (file) {
                    // Validate file type
                    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        toastr.error('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
                        // Clear the input to allow selecting the same file again
                        $input.val('');
                        return;
                    }

                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        toastr.error('Image size must not exceed 5MB');
                        // Clear the input to allow selecting the same file again
                        $input.val('');
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
                                // Clear the input to allow selecting the same file again
                                $input.val('');
                                // Show remove button
                                if (!$('#removeProfilePicBtn').length) {
                                    location.reload();
                                }
                            } else {
                                toastr.error(response.message);
                                // Clear the input on error
                                $input.val('');
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Failed to upload image');
                            // Clear the input on error
                            $input.val('');
                        }
                    });
                } else {
                    // Clear the input if no file selected
                    $input.val('');
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
