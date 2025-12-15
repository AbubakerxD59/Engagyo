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

