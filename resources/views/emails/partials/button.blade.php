@php
    $url = $url ?? '#';
    $text = $text ?? 'Continue';
    $primary = email_brand_color('primary');
    $primaryDark = email_brand_color('primary_dark');
@endphp
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:28px auto;">
    <tr>
        <td align="center" style="border-radius:8px;background:linear-gradient(135deg, {{ $primary }} 0%, {{ $primaryDark }} 100%);">
            <a href="{{ $url }}" target="_blank" style="display:inline-block;padding:14px 36px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                {{ $text }}
            </a>
        </td>
    </tr>
</table>
