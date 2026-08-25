<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;@if($props['shadow'])box-shadow:{{ $props['shadow'] }};@endif border-radius:{{ $props['radius'] }};">
                <tr>
                    @if($props['accent'])
                        <td width="4" bgcolor="{{ $props['accent'] }}" style="width:4px;background-color:{{ $props['accent'] }};font-size:0;line-height:0;">&nbsp;</td>
                    @endif
                    <td class="m-card" bgcolor="{{ $props['background'] }}" style="padding:{{ $props['padding'] }};background-color:{{ $props['background'] }};border:1px solid {{ $props['border_color'] }};@if($props['accent'])border-left:0;border-radius:0 {{ $props['radius'] }} {{ $props['radius'] }} 0;@else border-radius:{{ $props['radius'] }};@endif">
                        {!! $props['slot'] !!}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
