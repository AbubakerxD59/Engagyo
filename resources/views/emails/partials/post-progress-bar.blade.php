@php
    $posts = $posts ?? [];
    $total = max(0, (int) ($posts['total'] ?? 0));
    $published = max(0, (int) ($posts['published'] ?? 0));
    $failed = max(0, (int) ($posts['failed'] ?? 0));
    $pending = max(0, (int) ($posts['pending'] ?? 0));

    $colorPublished = '#16A34A';
    $colorFailed = '#DC2626';
    $colorPending = '#9CA3AF';
    $trackColor = '#E5E7EB';

    $segments = [];
    if ($total > 0) {
        $publishedPct = round(($published / $total) * 100, 1);
        $failedPct = round(($failed / $total) * 100, 1);
        $pendingPct = max(0, round(100 - $publishedPct - $failedPct, 1));

        if ($published > 0) {
            $segments[] = ['width' => $publishedPct, 'color' => $colorPublished, 'label' => 'Published', 'count' => $published];
        }
        if ($failed > 0) {
            $segments[] = ['width' => $failedPct, 'color' => $colorFailed, 'label' => 'Failed', 'count' => $failed];
        }
        if ($pending > 0) {
            $segments[] = ['width' => $pendingPct, 'color' => $colorPending, 'label' => 'Pending', 'count' => $pending];
        }
    }

    $text = email_brand_color('text');
    $textMuted = email_brand_color('text_muted');
    $marginTop = $marginTop ?? '12px';
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:{{ $marginTop }};">
    <tr>
        <td style="padding:0 0 8px;font-size:13px;font-weight:600;color:{{ $text }};">
            {{ $total }} {{ $total === 1 ? 'post' : 'posts' }} total
        </td>
    </tr>
    <tr>
        <td style="padding:0;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-radius:6px;overflow:hidden;background-color:{{ $trackColor }};border:1px solid {{ email_brand_color('border') }};">
                <tr>
                    @if(count($segments) > 0)
                        @foreach($segments as $segment)
                            <td width="{{ $segment['width'] }}%" height="10" style="background-color:{{ $segment['color'] }};font-size:0;line-height:0;mso-line-height-rule:exactly;">
                                &nbsp;
                            </td>
                        @endforeach
                    @else
                        <td height="10" style="background-color:{{ $trackColor }};font-size:0;line-height:0;">&nbsp;</td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:8px 0 0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="padding:0 14px 4px 0;font-size:12px;line-height:1.4;color:{{ $textMuted }};white-space:nowrap;">
                        <span style="display:inline-block;width:8px;height:8px;background-color:{{ $colorPublished }};border-radius:2px;margin-right:6px;vertical-align:middle;"></span>
                        <strong style="color:{{ $text }};">{{ $published }}</strong> published
                    </td>
                    <td style="padding:0 14px 4px 0;font-size:12px;line-height:1.4;color:{{ $textMuted }};white-space:nowrap;">
                        <span style="display:inline-block;width:8px;height:8px;background-color:{{ $colorFailed }};border-radius:2px;margin-right:6px;vertical-align:middle;"></span>
                        <strong style="color:{{ $text }};">{{ $failed }}</strong> failed
                    </td>
                    <td style="padding:0 0 4px;font-size:12px;line-height:1.4;color:{{ $textMuted }};white-space:nowrap;">
                        <span style="display:inline-block;width:8px;height:8px;background-color:{{ $colorPending }};border-radius:2px;margin-right:6px;vertical-align:middle;"></span>
                        <strong style="color:{{ $text }};">{{ $pending }}</strong> pending
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
