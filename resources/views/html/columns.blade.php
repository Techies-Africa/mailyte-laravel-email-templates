{{-- Real <td> cells, not inline-block divs. Inline-block elements sitting next
     to each other in the HTML source pick up a whitespace gap from the
     newline between them -- often just enough to push two 50% columns over
     100% and wrap the second one onto its own line, with no media query
     involved at all. Table cells have no such gap.

     Outlook's Word engine still gets its own ghost table with fixed pixel
     widths, since it does not honour percentage widths on <td> reliably. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:0 0 {{ $props['space_below'] }};">
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
                <td class="m-stack" valign="top" width="{{ $props['width_percent'] }}%" style="width:{{ $props['width_percent'] }}%;padding:0 {{ $props['gutter'] }} {{ $props['gutter'] }} 0;">
                    @if($item['icon'])
                        <div style="font-size:22px;line-height:1;margin:0 0 10px;">{{ $item['icon'] }}</div>
                    @endif
                    @if($item['heading'])
                        <p style="margin:0 0 4px;font-family:{{ $t['font.heading'] }};font-size:{{ $props['type_heading']['size'] ?? '14px' }};line-height:{{ $props['type_heading']['line_height'] ?? '22px' }};font-weight:600;color:{{ $props['heading_color'] }};">{{ $item['heading'] }}</p>
                    @endif
                    @if($item['text'])
                        <p style="margin:0;font-family:{{ $t['font.body'] }};font-size:13px;line-height:20px;color:{{ $props['text_color'] }};">{{ $item['text'] }}</p>
                    @endif
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
