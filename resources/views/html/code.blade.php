{{-- Live text, never an image: the code is the one string the reader actually
     needs, and an image of it is unselectable, unreadable to screen readers,
     and blocked by default in several clients. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td align="center" style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;">
                <tr>
                    <td align="center" bgcolor="{{ $props['background'] }}" style="padding:24px 16px;background-color:{{ $props['background'] }};border-radius:{{ $props['radius'] }};">
                        @if($props['label'] !== '')
                            <p style="margin:0 0 8px;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.small.size'] }};line-height:{{ $t['type.small.line_height'] }};color:{{ $t['color.text_muted'] }};text-transform:uppercase;letter-spacing:0.08em;">{{ $props['label'] }}</p>
                        @endif
                        <p style="margin:0;font-family:{{ $t['font.mono'] }};font-size:{{ $props['type']['size'] ?? '32px' }};line-height:{{ $props['type']['line_height'] ?? '40px' }};font-weight:{{ $props['type']['weight'] ?? '700' }};letter-spacing:{{ $props['type']['letter_spacing'] ?? '0.18em' }};color:{{ $props['color'] }};mso-line-height-rule:exactly;">{{ $props['code'] }}</p>
                        @if($props['note'] !== '')
                            <p style="margin:12px 0 0;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.small.size'] }};line-height:{{ $t['type.small.line_height'] }};color:{{ $t['color.text_muted'] }};">{{ $props['note'] }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
