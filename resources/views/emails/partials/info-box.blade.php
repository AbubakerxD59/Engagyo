@php
    $primary = email_brand_color('primary');
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;background-color:#F9FAFB;border-radius:8px;border-left:4px solid {{ $primary }};">
    <tr>
        <td style="padding:20px 24px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:15px;line-height:1.6;color:{{ email_brand_color('text') }};">
            {{ $slot }}
        </td>
    </tr>
</table>
