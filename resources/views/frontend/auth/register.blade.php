@extends('frontend.auth.layout.main')
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
                            @if($teamMember ?? false)
                                Accept Team Invitation
                            @else
                                Get Started
                            @endif
                        </h1>
                        @if($teamMember ?? false)
                            <div style="background-color: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                                <p style="margin: 0; color: #004085; font-size: 14px;">
                                    <strong>You've been invited to join a team!</strong><br>
                                    Complete your registration to accept the invitation.
                                </p>
                            </div>
                        @endif
                        <form action="{{ route('frontend.register') }}" method="POST" class="signup__input__form">
                            @csrf
                            @if($invitationToken ?? false)
                                <input type="hidden" name="invitation_token" value="{{ $invitationToken }}">
                            @endif
                            <div class="field">
                                <label for="first_name">First Name</label>
                                <input type="text" class="signup__input" id="first_name" name="first_name"
                                    value="{{ old('first_name', optional($teamMember ?? null)->first_name ?? '') }}" placeholder="Enter your first name" required>
                            </div>
                            <div class="field">
                                <label for="last_name">Last Name</label>
                                <input type="text" class="signup__input" id="last_name" name="last_name"
                                    value="{{ old('last_name', optional($teamMember ?? null)->last_name ?? '') }}" placeholder="Enter your last name" required>
                            </div>
                            <div class="field">
                                <label for="email">Email</label>
                                <input type="email" class="signup__input" id="email" name="email"
                                    value="{{ old('email', $invitationEmail ?? '') }}" placeholder="Enter your email address" autocomplete="off"
                                    {{ ($invitationEmail ?? false) ? 'readonly' : '' }} required>
                            </div>
                            <div class="field">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="signup__input"
                                    placeholder="Atleast 8 characters" required>
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
                            </div>
                            <button class="btn signup__btn">Sign Up</button>
                        </form>
                        <div class="signup__text__content">
                            <p>
                                Already have an account?
                                <a href="{{ route('frontend.showLogin') }}">Login</a>
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
