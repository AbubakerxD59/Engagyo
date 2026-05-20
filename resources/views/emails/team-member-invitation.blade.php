@extends('emails.layout')
@section('emailTitle', 'Team invitation — ' . email_app_name())
@section('emailContent')
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ email_brand_color('text') }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        You&rsquo;ve been invited to a team
    </h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        Hello,
    </p>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.65;color:{{ email_brand_color('text_muted') }};">
        <strong style="color:{{ email_brand_color('text') }};">{{ $teamLeadName }}</strong> invited you to collaborate on <strong style="color:{{ email_brand_color('text') }};">{{ email_app_name() }}</strong>. Accept the invitation to create your account and get access to shared accounts and scheduling tools.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:#F9FAFB;border-radius:8px;border-left:4px solid {{ email_brand_color('primary') }};">
        <tr>
            <td style="padding:20px 24px;font-size:15px;line-height:1.7;color:{{ email_brand_color('text') }};">
                <p style="margin:0 0 8px;"><strong>Team lead:</strong> {{ $teamLeadName }}</p>
                <p style="margin:0;"><strong>Your email:</strong> {{ $teamMember->email }}</p>
            </td>
        </tr>
    </table>

    @include('emails.partials.button', ['url' => $invitationUrl, 'text' => 'Accept invitation'])

    @include('emails.partials.link-fallback', ['url' => $invitationUrl])

    <p style="margin:16px 0 0;font-size:13px;line-height:1.5;color:#9CA3AF;">
        This invitation expires in 7 days. If you were not expecting this email, you can ignore it.
    </p>
@endsection
