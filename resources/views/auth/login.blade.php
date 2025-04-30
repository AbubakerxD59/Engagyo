@extends('admin.layouts.normal')
@section('page_title', 'Login')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/login-styles.css') }}">
@endpush
@section('page_content')
    <div class="login-box">
        <div class="login-holder">
            <div class="login-logo">
                <!-- <a href="#"><b>User Login</b></a> -->
                <img src="{{ site_logo() }}" alt="{{ env('APP_NAME') }}">
            </div>
            <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">{{ __('auth.login_page_title') }}</p>
                    <form method="post">
                        @csrf
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" name="email"
                                placeholder="{{ __('auth.login_email_placeholder') }}">
                            <div class="input-group-append">
                                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                            </div>
                        </div>
                        <div class="input-group mb-3">
                            <input type="password" class="form-control" name="password"
                                placeholder="{{ __('auth.login_password_placeholder') }}">
                            <div class="input-group-append">
                                <div class="input-group-text"><span class="fas fa-lock"></span></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <input type="hidden" name="remember_me" value="0">
                                <div class="icheck-primary">
                                    <input type="checkbox" id="remember_me" name="remember_me" value="1">
                                    <label for="remember_me">{{ __('auth.remember_me') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="social-auth-links text-center mb-3">
                            <button type="submit" class="btn btn-primary btn-block">{{ __('auth.btn_sign_in') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
