<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;">
                <tr>
                    <td bgcolor="{{ $props['background'] }}" style="padding:{{ $props['padding'] }};background-color:{{ $props['background'] }};border:1px solid {{ $props['border_color'] }};border-radius:{{ $props['radius'] }};">
                        {!! $props['slot'] !!}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
