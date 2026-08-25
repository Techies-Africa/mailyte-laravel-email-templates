{{-- Two columns only: two survive 320px with no media query, and email has no
     horizontal scroll. Outlook also rounds percentage widths up and will drop
     a cell onto a new row when several columns compete. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                @foreach($props['rows'] as $row)
                    @php($last = $props['emphasise_last'] && $loop->last)
                    <tr>
                        <td class="m-muted" valign="top" width="{{ $props['label_width'] }}" align="left" style="width:{{ $props['label_width'] }};padding:10px 12px 10px 0;border-bottom:1px solid {{ $props['border_color'] }};font-family:{{ $t['font.body'] }};font-size:{{ $props['type']['size'] ?? '14px' }};line-height:{{ $props['type']['line_height'] ?? '22px' }};color:{{ $props['label_color'] }};text-align:left;@if($last)font-weight:600;color:{{ $props['value_color'] }};@endif">{{ $row['label'] }}</td>
                        <td valign="top" align="{{ $props['figures'] ? 'right' : 'left' }}" style="padding:10px 0;border-bottom:1px solid {{ $props['border_color'] }};font-family:@if($row['mono']){{ $props['mono_font'] }}@else{{ $t['font.body'] }}@endif;font-size:{{ $props['type']['size'] ?? '14px' }};line-height:{{ $props['type']['line_height'] ?? '22px' }};color:{{ $props['value_color'] }};text-align:{{ $props['figures'] ? 'right' : 'left' }};@if($props['figures'])font-variant-numeric:tabular-nums;@endif @if($last)font-weight:700;font-size:17px;@endif @if($row['mono'])word-break:break-all;@endif">{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
