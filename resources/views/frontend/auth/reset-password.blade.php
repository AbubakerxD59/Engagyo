@extends('frontend.auth.layout.main')
@section('title', 'Reset Password')
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
                        <h1>Reset password</h1>
                        <form action="{{ route('frontend.password.update') }}" method="POST" class="signup__input__form">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <div class="field">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="signup__input"
                                    value="{{ old('email', $email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="field">
                                <label for="password">New password</label>
                                <input type="password" id="password" name="password" class="signup__input"
                                    placeholder="At least 8 characters" required>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="field">
                                <label for="password_confirmation">Confirm password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="signup__input" required>
                            </div>
                            <button type="submit" class="btn signup__btn">Reset password</button>
                        </form>
                        <div class="signup__text__content">
                            <p>
                                <a href="{{ route('frontend.showLogin') }}">Back to sign in</a>
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
