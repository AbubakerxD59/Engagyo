@extends('emails.layout')
@section('emailTitle', 'Weekly connected accounts report — ' . email_app_name())
@section('emailContent')
    @php
        $text = email_brand_color('text');
        $textMuted = email_brand_color('text_muted');
        $border = email_brand_color('border');
        $primary = email_brand_color('primary');
        $summary = $summary ?? [];
        $platforms = $platforms ?? [];
        $totalAccounts = (int) ($summary['totalAccounts'] ?? 0);
        $hasAccounts = $totalAccounts > 0;
    @endphp
    <h1 class="hero-title" style="margin:0 0 16px;font-size:26px;font-weight:700;color:{{ $text }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
        Your weekly connected accounts report
    </h1>
    <p style="margin:0 0 8px;font-size:16px;line-height:1.65;color:{{ $textMuted }};">
        Hi{{ $user->first_name ? ' ' . e($user->first_name) : '' }},
    </p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.65;color:{{ $textMuted }};">
        Here is a snapshot of your connected social accounts and publishing activity for
        <strong style="color:{{ $text }};">{{ $periodLabel }}</strong>.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:#F9FAFB;border-radius:8px;border:1px solid {{ $border }};">
        <tr>
            <td style="padding:20px 24px;font-size:15px;line-height:1.7;color:{{ $text }};">
                <p style="margin:0 0 12px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $textMuted }};">
                    Summary
                </p>
                <p style="margin:0 0 8px;"><strong>Connected accounts:</strong> {{ $totalAccounts }}</p>
                @if(isset($summary['limit']) && $summary['limit'] !== null && $summary['limit'] > 0)
                    <p style="margin:0 0 8px;"><strong>Plan limit (Facebook, Pinterest, TikTok):</strong> {{ $summary['packageTotal'] ?? 0 }} / {{ $summary['limit'] }}
                        @if(isset($summary['remaining']))
                            &mdash; <strong>{{ $summary['remaining'] }}</strong> slot(s) remaining
                        @endif
                    </p>
                @endif
                @include('emails.partials.post-progress-bar', [
                    'posts' => [
                        'total' => (int) ($summary['totalPosts'] ?? 0),
                        'published' => (int) ($summary['publishedPostsAll'] ?? 0),
                        'failed' => (int) ($summary['failedPostsAll'] ?? 0),
                        'pending' => (int) ($summary['pendingPostsAll'] ?? 0),
                    ],
                    'marginTop' => '0',
                ])
                <p style="margin:12px 0 8px;font-size:13px;color:{{ $textMuted }};">Activity in the last 7 days:</p>
                <p style="margin:0 0 8px;"><strong>Published this week:</strong> {{ (int) ($summary['publishedPosts'] ?? 0) }}</p>
                <p style="margin:0 0 8px;"><strong>Failed this week:</strong> {{ (int) ($summary['failedPosts'] ?? 0) }}</p>
                <p style="margin:0;"><strong>Currently in queue:</strong> {{ (int) ($summary['queuedPosts'] ?? 0) }}</p>
            </td>
        </tr>
    </table>

    @if($hasAccounts)
        @foreach($platforms as $platform)
            @if(($platform['count'] ?? 0) > 0)
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px;border:1px solid {{ $border }};border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:14px 18px;background-color:#F9FAFB;border-bottom:1px solid {{ $border }};">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="36" valign="middle">
                                        <img src="{{ $platform['logo'] }}" alt="" width="28" height="28" style="display:block;border-radius:50%;">
                                    </td>
                                    <td valign="middle" style="font-size:15px;font-weight:700;color:{{ $text }};">
                                        {{ $platform['label'] }}
                                        <span style="font-weight:600;color:{{ $textMuted }};">({{ $platform['count'] }})</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @foreach($platform['accounts'] as $account)
                        <tr>
                            <td style="padding:12px 18px;border-bottom:1px solid {{ $border }};">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td width="40" valign="top">
                                            <img src="{{ $account['imageUrl'] }}" alt="" width="32" height="32" style="display:block;border-radius:50%;border:1px solid {{ $border }};">
                                        </td>
                                        <td valign="top" style="font-size:14px;line-height:1.5;color:{{ $text }};">
                                            <strong>{{ e($account['name']) }}</strong>
                                            @if(!empty($account['subtitle']))
                                                <br><span style="color:{{ $textMuted }};font-size:13px;">{{ e($account['subtitle']) }}</span>
                                            @endif
                                            @include('emails.partials.post-progress-bar', [
                                                'posts' => $account['posts'] ?? [],
                                                'marginTop' => '10px',
                                            ])
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        @endforeach
    @else
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:#F9FAFB;border-radius:8px;border-left:4px solid {{ $primary }};">
            <tr>
                <td style="padding:20px 24px;font-size:15px;line-height:1.65;color:{{ $text }};">
                    You do not have any connected accounts yet. Connect Facebook, Pinterest, TikTok, Instagram, Threads, or LinkedIn to start scheduling and tracking your content.
                </td>
            </tr>
        </table>
    @endif

    @include('emails.partials.button', ['url' => $accountsUrl, 'text' => 'Manage connected accounts'])

    <p style="margin:20px 0 0;font-size:14px;color:{{ $textMuted }};text-align:center;">
        <a href="{{ $scheduleUrl }}" style="color:{{ $primary }};text-decoration:none;font-weight:600;">Open schedule dashboard</a>
    </p>

    <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#9CA3AF;">
        You receive this email every Sunday with an overview of your connected accounts. If you have questions, reply to our support team or contact us using the address in the footer.
    </p>
@endsection
