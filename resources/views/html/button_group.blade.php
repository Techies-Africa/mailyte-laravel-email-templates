<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td align="{{ $props['align'] }}" style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:separate;">
                <tr>
                    @foreach($props['items'] as $item)
                        @php($filled = in_array($item['variant'], ['primary', 'secondary', 'danger'], true))
                        @php($bg = match($item['variant']) {
                            'primary' => $props['primary_bg'],
                            'secondary' => $props['secondary_bg'],
                            'danger' => $props['danger_bg'],
                            default => 'transparent',
                        })
                        @php($ink = match($item['variant']) {
                            'primary', 'danger' => $props['primary_ink'],
                            'secondary' => $props['ink'],
                            default => $props['ink'],
                        })
                        <td valign="middle" style="padding:0 {{ $loop->last ? '0' : '10px' }} 0 0;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:separate;">
                                <tr>
                                    <td align="center" @if(in_array($item['variant'], ['primary', 'danger'], true)) class="{{ $item['variant'] === 'danger' ? 'm-btn-danger' : 'm-btn-plate' }}" @endif @if($filled) bgcolor="{{ $bg }}" @endif style="border-radius:{{ $props['radius'] }};@if(! $filled)border:1px solid {{ $props['border_color'] }};@endif">
                                        <a @if($filled) class="m-btn @if($item['variant'] === 'primary')m-btn-plate @elseif($item['variant'] === 'danger')m-btn-danger @endif" @endif href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                           style="display:inline-block;padding:{{ $props['padding_y'] }} {{ $props['padding_x'] }};@if($filled)background-color:{{ $bg }};@endif border-radius:{{ $props['radius'] }};font-family:{{ $t['font.heading'] }};font-size:{{ $props['type']['size'] ?? '15px' }};line-height:{{ $props['type']['line_height'] ?? '15px' }};font-weight:{{ $props['type']['weight'] ?? '600' }};color:{{ $ink }};text-decoration:none;mso-padding-alt:0;mso-line-height-rule:exactly;">
                                            <!--[if mso]><i style="mso-font-width:-100%;mso-text-raise:{{ $props['padding_y'] }}" hidden>&nbsp;</i><![endif]-->
                                            <span style="mso-text-raise:6px;">{{ $item['label'] }}</span>
                                            <!--[if mso]><i style="mso-font-width:-100%" hidden>&nbsp;</i><![endif]-->
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
