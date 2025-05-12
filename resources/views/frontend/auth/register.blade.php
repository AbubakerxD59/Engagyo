@extends('frontend..auth.layout.main')
@section('title', 'Signup')
@section('authBody')
    <div class="container-fluid p-0 m-0 overflow-xx-hidden">
        <div class="row">
            <div class="col-12 col-lg-5 p-0 justify-self d-grid align-items-center p-4">
                <div class="signup__container">
                    <div class="signup__logo">
                        <a href="{{ route('frontend.home') }}">
                            <img src="{{ site_logo() }}" alt="">
                        </a>
                    </div>
                    <div class="text-wrapper">
                        <h1>
                            Get Started
                        </h1>
                        <form action="{{ route('frontend.register') }}" method="POST" class="signup__input__form">
                            @csrf
                            <div class="signup__input__group">
                                <div class="field input__group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" class="w-100" id="first_name" name="first_name"
                                        value="{{ old('first_name') }}" placeholder="Enter your first name" required>
                                    @error('first_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="field input__group input__group__right">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" class="w-100" id="last_name" name="last_name"
                                        value="{{ old('last_name') }}" placeholder="Enter your last name" required>
                                    @error('last_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="field">
                                <label for="email">Email</label>
                                <input type="email" class="signup__input" id="email" name="email"
                                    value="{{ old('email') }}" placeholder="Enter your email address" autocomplete="off"
                                    required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="field">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="signup__input"
                                    placeholder="Atleast 8 characters" required>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="checkarea">
                                <input type="checkbox" name="agreement" id="agreement" class="w-auto agree-checkbox"
                                    {{ old('agreement') == 'on' ? 'checked' : '' }} required>
                                <label for="agreement">
                                    <p>
                                        By continuing you agree to our
                                        <a href="/terms" target="_blank">Terms</a>
                                        and
                                        <a href="/privacy" target="_blank">Privacy</a>
                                    </p>
                                </label>
                                @error('agreement')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button class="btn signup__btn">Sign Up</button>
                        </form>
                        <div class="signup__text__content">
                            <p>
                                Already have an account?
                                <a href="{{ route('frontend.showLogin') }}">Login In</a>
                            </p>
                        </div>
                    </div>
                    <div class="signup__bottom">
                        <div class="text-wrapper">
                            <p class="small-desc p-0 m-0">
                                {{ '@ ' . date('Y') . ' ' . site_company() }}
                                <a href="#">Terms</a> and <a href="">Privacy</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7 m-0 p-0 d-none d-lg-flex position-relative">
                <div class="dolottie">
                    <div class="sticky-content">
                        <img src="{{ asset('assets/frontend/images/signuppage-Welcome.svg') }}" class="h-100 w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
