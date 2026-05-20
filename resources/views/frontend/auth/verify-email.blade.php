@extends('frontend.auth.layout.main')
@section('title', 'Verify Email')
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
                        <h1>Verify your email</h1>
                        <p style="color: #666; margin-bottom: 12px;">
                            @if($emailJustSent ?? false)
                                We&rsquo;ve sent a verification link to <strong>{{ $user->email }}</strong>.
                            @else
                                A verification link was recently sent to <strong>{{ $user->email }}</strong>.
                            @endif
                            Please check your inbox (and spam folder) and click the link to activate your account.
                        </p>
                        <p style="color: #888; font-size: 14px; margin-bottom: 20px;">
                            Didn&rsquo;t receive it?
                        </p>
                        <form action="{{ route('frontend.verification.send') }}" method="POST" class="signup__input__form">
                            @csrf
                            <button type="submit" class="btn signup__btn" style="background: transparent; color: #007bff; border: 1px solid #007bff;">
                                Resend verification email
                            </button>
                        </form>
                        <div class="signup__text__content" style="margin-top: 20px;">
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
