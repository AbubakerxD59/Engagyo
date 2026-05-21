@extends('emails.layout')
@section('emailTitle', 'Post failed to publish — ' . email_app_name())
@section('emailContent')
    @php
        $errorColor = '#DC2626';
        $errorBg = '#FEF2F2';
        $text = email_brand_color('text');
        $textMuted = email_brand_color('text_muted');
        $border = email_brand_color('border');
    @endphp
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ $text }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        Your post could not be published
    </h1>
    <p style="margin:0 0 20px;font-size:16px;line-height:1.65;color:{{ $textMuted }};">
        Hi{{ $user && $user->first_name ? ' ' . e($user->first_name) : '' }},
        we tried to publish your {{ strtolower($platform) }} {{ strtolower($postType) }} but it did not go through. Review the details below and try again from your dashboard.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:{{ $errorBg }};border-radius:8px;border-left:4px solid {{ $errorColor }};">
        <tr>
            <td style="padding:20px 24px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
                <p style="margin:0 0 8px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $errorColor }};">
                    Error
                </p>
                <p style="margin:0;font-size:15px;line-height:1.65;color:{{ $text }};">
                    {{ $errorMessage }}
                </p>
            </td>
        </tr>
    </table>

    @if(!empty($imageUrl) && !str_contains($imageUrl, 'placeholder'))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td align="center" style="padding:0;">
                <img src="{{ $imageUrl }}" alt="Post preview" width="280" style="display:block;max-width:100%;height:auto;border-radius:8px;border:1px solid {{ $border }};">
            </td>
        </tr>
    </table>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:#F9FAFB;border-radius:8px;border:1px solid {{ $border }};">
        <tr>
            <td style="padding:20px 24px;font-size:15px;line-height:1.7;color:{{ $text }};">
                <p style="margin:0 0 16px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $textMuted }};">
                    Post details
                </p>
                <p style="margin:0 0 10px;"><strong>Post ID:</strong> #{{ $postId }}</p>
                <p style="margin:0 0 10px;"><strong>Platform:</strong> {{ $platform }}</p>
                <p style="margin:0 0 10px;"><strong>Post type:</strong> {{ $postType }}</p>
                <p style="margin:0 0 10px;"><strong>Source:</strong> {{ $source }}</p>
                @if($accountName !== '' || $accountUsername !== '')
                <p style="margin:0 0 10px;"><strong>Account:</strong>
                    {{ $accountName !== '' ? e($accountName) : '' }}
                    @if($accountUsername !== '')
                        @if($accountName !== '') &mdash; @endif
                        {{ '@' . e($accountUsername) }}
                    @endif
                </p>
                @endif
                @if($title !== '')
                <p style="margin:0 0 10px;"><strong>Title:</strong> {{ e($title) }}</p>
                @endif
                @if($description)
                <p style="margin:0 0 10px;"><strong>Caption / description:</strong> {{ e($description) }}</p>
                @endif
                @if($comment)
                <p style="margin:0 0 10px;"><strong>First comment:</strong> {{ e($comment) }}</p>
                @endif
                @if($url !== '')
                <p style="margin:0 0 10px;"><strong>Link URL:</strong> <a href="{{ $url }}" style="color:{{ email_brand_color('primary') }};word-break:break-all;">{{ e($url) }}</a></p>
                @endif
                @if($domainName !== '')
                <p style="margin:0 0 10px;"><strong>Domain:</strong> {{ e($domainName) }}</p>
                @endif
                @if($apiKeyName)
                <p style="margin:0 0 10px;"><strong>API key:</strong> {{ e($apiKeyName) }}</p>
                @endif
                @if($hasVideo)
                <p style="margin:0 0 10px;"><strong>Media:</strong> Video attached</p>
                @endif
                @if($scheduledAt)
                <p style="margin:0 0 10px;"><strong>Scheduled for:</strong> {{ $scheduledAt }}</p>
                @endif
                @if($failedAt)
                <p style="margin:0;"><strong>Failed at:</strong> {{ $failedAt }}</p>
                @endif
            </td>
        </tr>
    </table>

    @include('emails.partials.button', ['url' => $panelUrl, 'text' => 'Open schedule dashboard'])

    <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#9CA3AF;">
        You also received an in-app notification. Reconnect the account or edit the post if the error mentions permissions, tokens, or media requirements.
    </p>
@endsection
