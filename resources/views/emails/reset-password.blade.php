@extends('emails.layout')
@section('emailTitle', 'Reset your password — ' . email_app_name())
@section('emailContent')
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ email_brand_color('text') }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        Reset your password
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        Hi{{ $user->first_name ? ' ' . e($user->first_name) : '' }},
    </p>
    <p style="margin:0 0 8px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        We received a request to reset the password for your <strong style="color:{{ email_brand_color('text') }};">{{ email_app_name() }}</strong> account. Click below to choose a new password.
    </p>

    @include('emails.partials.button', ['url' => $resetUrl, 'text' => 'Reset password'])

    @include('emails.partials.link-fallback', ['url' => $resetUrl])

    <p style="margin:16px 0 0;font-size:13px;line-height:1.5;color:#9CA3AF;">
        This link expires in {{ $expireMinutes }} minutes. If you did not request a reset, no action is needed — your password will stay the same.
    </p>
@endsection
