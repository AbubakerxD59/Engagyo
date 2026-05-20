@php
    $brandName = email_app_name();
    $primary = email_brand_color('primary');
    $primaryDark = email_brand_color('primary_dark');
    $accent = email_brand_color('accent');
    $logoUrl = email_logo_url();
    $tagline = config('mail_branding.tagline');
    $supportEmail = config('mail_branding.support_email');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('emailTitle', $brandName)</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, p, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: {{ email_brand_color('background') }}; }
        .email-body-copy { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        @media only screen and (max-width: 620px) {
            .wrapper { width: 100% !important; padding: 16px !important; }
            .content-cell { padding: 28px 20px !important; }
            .hero-title { font-size: 22px !important; }
        }
    </style>
</head>
<body class="email-body-copy" style="margin:0;padding:0;background-color:{{ email_brand_color('background') }};">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:{{ email_brand_color('background') }};">
        <tr>
            <td align="center" class="wrapper" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;">
                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg, {{ $primary }} 0%, {{ $accent }} 100%);border-radius:12px 12px 0 0;padding:36px 24px;">
                            <a href="{{ route('frontend.home') }}" style="text-decoration:none;">
                                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" width="160" style="display:block;max-width:160px;height:auto;margin:0 auto 12px;">
                            </a>
                            @if($tagline)
                            <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.9);letter-spacing:0.3px;">{{ $tagline }}</p>
                            @endif
                        </td>
                    </tr>
                    {{-- Body card --}}
                    <tr>
                        <td class="content-cell" style="background-color:{{ email_brand_color('card') }};padding:40px 36px;border-left:1px solid {{ email_brand_color('border') }};border-right:1px solid {{ email_brand_color('border') }};">
                            @yield('emailContent')
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#F9FAFB;border-radius:0 0 12px 12px;padding:28px 36px;border:1px solid {{ email_brand_color('border') }};border-top:none;text-align:center;">
                            <p style="margin:0 0 8px;font-size:13px;color:{{ email_brand_color('text_muted') }};">
                                &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
                            </p>
                            <p style="margin:0 0 12px;font-size:13px;">
                                <a href="{{ route('frontend.home') }}" style="color:{{ $primary }};text-decoration:none;font-weight:600;">Website</a>
                                <span style="color:#D1D5DB;">&nbsp;&middot;&nbsp;</span>
                                <a href="{{ route('frontend.terms') }}" style="color:{{ $primary }};text-decoration:none;">Terms</a>
                                <span style="color:#D1D5DB;">&nbsp;&middot;&nbsp;</span>
                                <a href="{{ route('frontend.privacy') }}" style="color:{{ $primary }};text-decoration:none;">Privacy</a>
                            </p>
                            @if($supportEmail)
                            <p style="margin:0;font-size:12px;color:#9CA3AF;">
                                Questions? <a href="mailto:{{ $supportEmail }}" style="color:{{ $primary }};text-decoration:none;">{{ $supportEmail }}</a>
                            </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
