@extends('emails.layout')
@section('emailTitle', 'Welcome to ' . email_app_name())
@section('emailContent')
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ email_brand_color('text') }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        You&rsquo;re all set, welcome aboard!
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        Hi{{ $user->first_name ? ' ' . e($user->first_name) : '' }},
    </p>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        Your email is verified and your <strong style="color:{{ email_brand_color('text') }};">{{ email_app_name() }}</strong> account is ready. Here&rsquo;s what you can do next:
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td style="padding:12px 0;font-size:15px;color:{{ email_brand_color('text') }};border-bottom:1px solid {{ email_brand_color('border') }};">
                <span style="color:{{ email_brand_color('primary') }};font-weight:700;margin-right:8px;">1.</span> Connect Facebook, Pinterest, TikTok, and more
            </td>
        </tr>
        <tr>
            <td style="padding:12px 0;font-size:15px;color:{{ email_brand_color('text') }};border-bottom:1px solid {{ email_brand_color('border') }};">
                <span style="color:{{ email_brand_color('primary') }};font-weight:700;margin-right:8px;">2.</span> Schedule posts and manage your content calendar
            </td>
        </tr>
        <tr>
            <td style="padding:12px 0;font-size:15px;color:{{ email_brand_color('text') }};">
                <span style="color:{{ email_brand_color('primary') }};font-weight:700;margin-right:8px;">3.</span> Invite teammates and collaborate on campaigns
            </td>
        </tr>
    </table>

    @include('emails.partials.button', ['url' => $panelUrl, 'text' => 'Open your dashboard'])

    <p style="margin:20px 0 0;font-size:14px;color:{{ email_brand_color('text_muted') }};text-align:center;">
        <a href="{{ $loginUrl }}" style="color:{{ email_brand_color('primary') }};text-decoration:none;font-weight:600;">Sign in again</a>
    </p>
@endsection
