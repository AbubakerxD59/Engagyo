@extends('emails.layout')
@section('emailTitle', 'Verify your email — ' . email_app_name())
@section('emailContent')
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ email_brand_color('text') }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        Confirm your email address
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        Hi{{ $user->first_name ? ' ' . e($user->first_name) : '' }},
    </p>
    <p style="margin:0 0 8px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        Thanks for joining <strong style="color:{{ email_brand_color('text') }};">{{ email_app_name() }}</strong>. One quick step left — verify your email so you can access your dashboard, connect social accounts, and start scheduling content.
    </p>

    @include('emails.partials.button', ['url' => $verificationUrl, 'text' => 'Verify my email'])

    @include('emails.partials.link-fallback', ['url' => $verificationUrl])

    <p style="margin:16px 0 0;font-size:13px;line-height:1.5;color:#9CA3AF;">
        This link expires in {{ config('auth.verification.expire', 60) }} minutes. If you did not create an account, you can safely ignore this message.
    </p>
@endsection
