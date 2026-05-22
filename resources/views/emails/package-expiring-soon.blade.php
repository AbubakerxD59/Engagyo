@extends('emails.layout')
@section('emailTitle', 'Package expiring soon — ' . email_app_name())
@section('emailContent')
    @php
        $warningColor = '#D97706';
        $warningBg = '#FFFBEB';
        $text = email_brand_color('text');
        $textMuted = email_brand_color('text_muted');
        $border = email_brand_color('border');
    @endphp
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ $text }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        Your plan expires soon
    </h1>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.65;color:{{ $textMuted }};">
        Hi{{ $user->first_name ? ' ' . e($user->first_name) : '' }},
        your <strong style="color:{{ $text }};">{{ e($packageName) }}</strong> package on {{ email_app_name() }} will expire in
        <strong style="color:{{ $warningColor }};">{{ $warningDays }} {{ $warningDays === 1 ? 'day' : 'days' }}</strong>.
        Renew now to keep scheduling posts and using your connected accounts without interruption.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:{{ $warningBg }};border-radius:8px;border-left:4px solid {{ $warningColor }};">
        <tr>
            <td style="padding:20px 24px;font-size:15px;line-height:1.7;color:{{ $text }};">
                <p style="margin:0 0 8px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $warningColor }};">
                    Expiration date
                </p>
                <p style="margin:0 0 8px;"><strong>Package:</strong> {{ e($packageName) }}</p>
                <p style="margin:0;"><strong>Expires:</strong> {{ $expiresAtFormatted }}</p>
            </td>
        </tr>
    </table>

    @include('emails.partials.button', ['url' => $planBillingUrl, 'text' => 'Renew or upgrade plan'])

    <p style="margin:20px 0 0;font-size:14px;color:{{ $textMuted }};text-align:center;">
        <a href="{{ $loginUrl }}" style="color:{{ email_brand_color('primary') }};text-decoration:none;font-weight:600;">Sign in to your account</a>
    </p>

    <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#9CA3AF;">
        After your package expires, publishing and other paid features may be limited until you renew.
    </p>
@endsection
