@php
    $url = $url ?? '';
@endphp
<p style="margin:24px 0 8px;font-size:13px;color:{{ email_brand_color('text_muted') }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
    If the button above does not work, copy and paste this link into your browser:
</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;background-color:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;">
    <tr>
        <td style="padding:14px 16px;font-size:12px;line-height:1.5;color:#92400E;word-break:break-all;font-family:monospace;">
            {{ $url }}
        </td>
    </tr>
</table>
