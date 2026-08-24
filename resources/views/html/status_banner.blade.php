{{-- Severity is carried four ways at once -- word, glyph shape, border weight,
     and colour last -- so it survives image blocking, forced dark-mode
     inversion, greyscale, and screen readers. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td width="4" bgcolor="{{ $props['accent'] }}" style="width:4px;background-color:{{ $props['accent'] }};font-size:0;line-height:0;">&nbsp;</td>
                    <td class="m-alt" bgcolor="{{ $props['background'] }}" style="padding:14px 16px;background-color:{{ $props['background'] }};font-family:{{ $t['font.body'] }};font-size:{{ $props['type']['size'] ?? '16px' }};line-height:{{ $props['type']['line_height'] ?? '26px' }};color:{{ $props['color'] }};">
                        <span style="font-weight:700;letter-spacing:0.06em;color:{{ $props['accent'] }};">{{ $props['glyph'] }} {{ $props['label'] }}</span>
                        @if($props['text'] !== '')
                            <span style="display:block;margin-top:4px;">{{ $props['text'] }}</span>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
