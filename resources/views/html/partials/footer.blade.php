<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:16px 0 0;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr><td class="m-divider" height="1" style="height:1px;line-height:1px;font-size:0;background-color:{{ $t['color.border'] }};">&nbsp;</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="m-muted" style="padding:16px 0 0;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.footer.size'] ?? '13px' }};line-height:{{ $t['type.footer.line_height'] ?? '20px' }};color:{{ $t['color.text_muted'] }};">
            @if(($t['footer.reason'] ?? null))
                <p style="margin:0 0 8px;">{{ $t['footer.reason'] }}</p>
            @endif
            @if(($t['footer.legal'] ?? null))
                <p style="margin:0 0 8px;">{{ $t['footer.legal'] }}</p>
            @endif
            @if(($t['footer.address'] ?? null) || ($globals['company']['address'] ?? null))
                <p style="margin:0 0 8px;">{{ $t['footer.address'] ?? $globals['company']['address'] }}</p>
            @endif
            @if(($globals['unsubscribe_url'] ?? null) || ($globals['preferences_url'] ?? null))
                <p style="margin:0;">
                    @if(($globals['unsubscribe_url'] ?? null))
                        <a href="{{ $globals['unsubscribe_url'] }}" style="color:{{ $t['color.text_muted'] }};text-decoration:underline;">{{ $t['footer.unsubscribe_text'] ?? 'Unsubscribe' }}</a>
                    @endif
                    @if(($globals['unsubscribe_url'] ?? null) && ($globals['preferences_url'] ?? null)) &nbsp;&middot;&nbsp; @endif
                    @if(($globals['preferences_url'] ?? null))
                        <a href="{{ $globals['preferences_url'] }}" style="color:{{ $t['color.text_muted'] }};text-decoration:underline;">{{ $t['footer.preferences_text'] ?? 'Email preferences' }}</a>
                    @endif
                </p>
            @endif
        </td>
    </tr>
</table>
