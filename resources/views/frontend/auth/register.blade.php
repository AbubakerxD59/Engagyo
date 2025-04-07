@extends('frontend..auth.layout.main')
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
                        <form action="" class="signup__input__form">
                            <div class="signup__input__group">
                                <div class="field input__group">
                                    <label for="">First Name</label>
                                    <input type="text" class="w-100" placeholder="First Name">
                                </div>
                                <div class="field input__group input__group__right">
                                    <label for="">Last Name</label>
                                    <input type="text" class="w-100" placeholder="Last Name">
                                </div>
                            </div>
                            <div class="field">
                                <label for="text">Email</label>
                                <input type="text" class="signup__input" placeholder="Enter your email address">
                            </div>
                            <div class="field">
                                <label for="text">Password</label>
                                <input type="password" class="signup__input" placeholder="Atleast 8 characters">
                            </div>
                            <div class="checkarea">
                                <input type="checkbox" name="agreement" class="w-auto agree-checkbox" required>
                                <p>
                                    By continuing you agree to our <a href="/terms" target="_blank">Terms</a> and <a
                                        href="/privacy" target="_blank">Privacy</a>
                                </p>
                            </div>
                            <button class="btn signup__btn">Sign Up</button>
                        </form>
                        <div class="signup__text__content">
                            <p>
                                Already have an account?
                                <a href="{{ route('frontend.showLogin') }}"> Sign in</a>
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
