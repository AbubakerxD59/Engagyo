@extends('frontend.auth.layout.main')
@section('title', 'Signin')
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
                        <h1>
                            Welcome
                        </h1>
                        <form action="{{ route('frontend.login') }}" method="POST" class="signup__input__form">
                            @csrf
                            <div class="field">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="signup__input"
                                    placeholder="Enter your email address" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="field">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="signup__input"
                                    autocomplete="off" required>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="checkarea">
                                <input type="checkbox" name="remember_me" id="remember_me" class="w-auto agree-checkbox"
                                    {{ old('remember_me') == 'on' ? 'checked' : '' }}>
                                <label for="remember_me">
                                    <p>
                                        Remember me
                                    </p>
                                </label>
                                @error('remember_me')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button class="btn signup__btn">Sign In</button>
                        </form>
                        <div class="signup__text__content">
                            <p>
                                Don't have an account?
                                <a href="{{ route('frontend.showRegister') }}">Register now</a>
                            </p>
                        </div>
                    </div>
                    <div class="signup__bottom">
                        <div class="text-wrapper">
                            <p class="small-desc p-0 m-0">
                                {{ '@ ' . date('Y') . ' ' . site_company() }}
                                <a href="#">Terms</a> and <a href="#">Privacy</a>
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
