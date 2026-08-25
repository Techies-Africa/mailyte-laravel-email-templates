<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td align="{{ $props['align'] }}" style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <h{{ $props['level'] }} class="m-h{{ $props['level'] }}" style="margin:0;font-family:{{ $t['font.heading'] }};font-size:{{ $props['type']['size'] ?? '28px' }};line-height:{{ $props['type']['line_height'] ?? '36px' }};font-weight:{{ $props['type']['weight'] ?? '700' }};letter-spacing:{{ $props['type']['letter_spacing'] ?? '0' }};color:{{ $props['color'] }};mso-line-height-rule:exactly;">{{ $props['text'] }}</h{{ $props['level'] }}>
        </td>
    </tr>
</table>
