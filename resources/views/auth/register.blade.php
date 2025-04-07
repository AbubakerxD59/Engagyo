@extends('admin.layouts.normal')
@section('page_title', 'Register')
@section('page_content')
<div>
    <div class="register-logo">
        <a href="#"><b>{{ __('register.page_heading') }}</b></a>
    </div>
    <div class="card">
        <div class="card-body register-card-body">
            <p class="login-box-msg">{{ __('register.page_sub_heading') }}</p>
            <form wire:submit.prevent="register" method="post">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" wire:model="first_name" placeholder="{{ __('register.first_name_placeholder') }}" />
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-user"></span></div>
                    </div>
                    @error('first_name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" wire:model="last_name" placeholder="{{ __('register.last_name_placeholder') }}" />
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-user"></span></div>
                    </div>
                    @error('last_name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="input-group mb-3">
                    <input type="email" class="form-control" wire:model="email" placeholder="{{ __('register.email_placeholder') }}" />
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                    </div>
                    @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" wire:model="password" placeholder="{{ __('register.password_placeholder') }}" />
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                    @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" wire:model="password_confirmation" placeholder="{{ __('register.confirm_password_placeholder') }}" />
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                    @error('password_confirmation')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="social-auth-links text-center">
                    <button type="submit" class="btn btn-primary btn-block">{{ __('register.btn_register') }}</button>
                </div>
            </form>
            <div class="text-center">
                <a href="{{route('login')}}">{{ __('register.already_have_membership') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection