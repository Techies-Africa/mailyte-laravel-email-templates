@php($glyphs = ['bullet' => '&bull;', 'check' => '&#10003;', 'plain' => '', 'number' => ''])
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:0 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                @foreach($props['items'] as $index => $item)
                    <tr>
                        @if($props['style'] !== 'plain')
                            <td valign="top" width="24" style="width:24px;padding:0 8px 10px 0;font-family:{{ $t['font.body'] }};font-size:{{ $props['type']['size'] ?? '16px' }};line-height:{{ $props['type']['line_height'] ?? '26px' }};color:{{ $props['color'] }};">@if($props['style'] === 'number'){{ $index + 1 }}.@else{!! $glyphs[$props['style']] !!}@endif</td>
                        @endif
                        <td valign="top" style="padding:0 0 10px;font-family:{{ $t['font.body'] }};font-size:{{ $props['type']['size'] ?? '16px' }};line-height:{{ $props['type']['line_height'] ?? '26px' }};color:{{ $props['color'] }};">
                            {{ $item['text'] }}
                            @if($item['detail'] !== '')
                                <span style="display:block;color:{{ $props['muted_color'] }};font-size:{{ $t['type.small.size'] }};line-height:{{ $t['type.small.line_height'] }};">{{ $item['detail'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
