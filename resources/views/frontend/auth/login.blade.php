@extends('frontend.auth.layout.main')
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
                        <form action="" class="signup__input__form">
                            <div class="field">
                                <label for="text">Email</label>
                                <input type="text" class="signup__input" placeholder="Enter your email address">
                            </div>
                            <div class="field">
                                <label for="text">Password</label>
                                <input type="password" class="signup__input" placeholder="Atleast 8 characters"
                                    autocomplete="off">
                            </div>
                            <button class="btn signup__btn">Sign Up</button>
                        </form>
                        <div class="signup__text__content">
                            <p>
                                Don't have an account?
                                <a href="{{ route('frontend.showRegister') }}"> Register now</a>
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
