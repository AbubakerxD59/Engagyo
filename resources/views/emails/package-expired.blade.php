@extends('emails.layout')
@section('emailTitle', 'Package expired — ' . email_app_name())
@section('emailContent')
    @php
        $errorColor = '#DC2626';
        $errorBg = '#FEF2F2';
        $text = email_brand_color('text');
        $textMuted = email_brand_color('text_muted');
    @endphp
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ $text }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        Your package has expired
    </h1>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.65;color:{{ $textMuted }};">
        Hi{{ $user->first_name ? ' ' . e($user->first_name) : '' }},
        your <strong style="color:{{ $text }};">{{ e($packageName) }}</strong> package on {{ email_app_name() }} has expired.
        Please upgrade or renew to continue working — scheduling, publishing, and other plan features are limited until your subscription is active again.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:{{ $errorBg }};border-radius:8px;border-left:4px solid {{ $errorColor }};">
        <tr>
            <td style="padding:20px 24px;font-size:15px;line-height:1.7;color:{{ $text }};">
                <p style="margin:0 0 8px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $errorColor }};">
                    Action required
                </p>
                <p style="margin:0 0 8px;"><strong>Package:</strong> {{ e($packageName) }}</p>
                <p style="margin:0;"><strong>Expired:</strong> {{ $expiresAtFormatted }}</p>
            </td>
        </tr>
    </table>

    @include('emails.partials.button', ['url' => $planBillingUrl, 'text' => 'Upgrade or renew now'])

    <p style="margin:20px 0 0;font-size:14px;color:{{ $textMuted }};text-align:center;">
        <a href="{{ $loginUrl }}" style="color:{{ email_brand_color('primary') }};text-decoration:none;font-weight:600;">Sign in to your account</a>
    </p>

    <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#9CA3AF;">
        If you have already renewed, you can ignore this message. Your access will be restored once payment is confirmed.
    </p>
@endsection
