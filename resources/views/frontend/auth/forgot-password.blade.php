@extends('frontend.auth.layout.main')
@section('title', 'Forgot Password')
@section('authBody')
    <div class="container-fluid m-0 p-0">
        <div class="row m-0 p-0 align-items-center">
            <div class="col-12 col-lg-5 p-0 justify-self d-grid align-items-center p-4">
                <div class="signup__container">
                    <div class="signup__logo">
                        <a href="{{ route('frontend.home') }}">
                            <img src="{{ site_logo() }}" alt="">
                        </a>
                    </div>
                    <div class="text-wrapper">
                        <h1>Forgot password</h1>
                        <p style="color: #666; margin-bottom: 20px;">
                            Enter your email address and we will send you a link to reset your password.
                        </p>
                        <form action="{{ route('frontend.password.email') }}" method="POST" class="signup__input__form">
                            @csrf
                            <div class="field">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="signup__input"
                                    placeholder="Enter your email address" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn signup__btn">Send reset link</button>
                        </form>
                        <div class="signup__text__content">
                            <p>
                                Remember your password?
                                <a href="{{ route('frontend.showLogin') }}">Sign in</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7 m-0 p-0">
                <div class="dotlottie">
                    <div class="sticky-content">
                        <img src="{{ asset('assets/frontend/images/logininpage-Welcome.png') }}" class="responsive-image">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
