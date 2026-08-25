<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
            @foreach($props['items'] as $item)
                <td valign="top" width="{{ $props['ghost_width'] }}">
            @endforeach
            <![endif]-->
            <!--[if !mso]><!-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;"><tr>
            <!--<![endif]-->
            @foreach($props['items'] as $item)
                <td class="m-stack" valign="top" width="{{ $props['width_percent'] }}%" style="width:{{ $props['width_percent'] }}%;padding:0 {{ $loop->last ? '0' : '10px' }} 10px 0;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <tr>
                            <td @class(['m-card' => $props['boxed']]) align="{{ $props['align'] }}" style="padding:{{ $props['boxed'] ? '16px 18px' : '0' }};@if($props['boxed'])background-color:{{ $props['background'] }};border-radius:{{ $props['radius'] }};@endif">
                                <p class="{{ (int) str_replace('px', '', $props['value_size']) >= 40 ? 'm-stat-display' : 'm-stat' }}" style="margin:0;font-family:{{ $t['font.heading'] }};font-size:{{ $props['value_size'] }};line-height:1.15;font-weight:700;letter-spacing:-0.02em;color:{{ $props['value_color'] }};">{{ $item['value'] }}</p>
                                @if($item['label'])
                                    <p class="m-muted" style="margin:6px 0 0;font-family:{{ $t['font.body'] }};font-size:13px;line-height:19px;color:{{ $props['label_color'] }};">{{ $item['label'] }}</p>
                                @endif
                                @if($item['caption'])
                                    <p class="m-muted" style="margin:3px 0 0;font-family:{{ $t['font.body'] }};font-size:12px;line-height:18px;color:{{ $props['caption_color'] }};">{{ $item['caption'] }}</p>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            @endforeach
            <!--[if !mso]><!-->
            </tr></table>
            <!--<![endif]-->
            <!--[if mso]>
            </tr></table>
            <![endif]-->
        </td>
    </tr>
</table>
