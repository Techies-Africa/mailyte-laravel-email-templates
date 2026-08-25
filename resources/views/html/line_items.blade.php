<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                @foreach($props['items'] as $item)
                    <tr>
                        @if($props['show_thumbs'])
                            <td valign="top" width="{{ $props['thumb_size'] }}" style="width:{{ $props['thumb_size'] }}px;padding:14px 14px 14px 0;border-bottom:1px solid {{ $props['border_color'] }};">
                                @if($item['image'])
                                    <img src="{{ $item['image'] }}" alt="" width="{{ $props['thumb_size'] }}" height="{{ $props['thumb_size'] }}" style="display:block;border:0;width:{{ $props['thumb_size'] }}px;height:{{ $props['thumb_size'] }}px;border-radius:{{ $props['radius'] }};">
                                @endif
                            </td>
                        @endif
                        <td valign="top" align="left" style="padding:14px 12px 14px 0;border-bottom:1px solid {{ $props['border_color'] }};font-family:{{ $t['font.body'] }};">
                            <p style="margin:0;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:22px;font-weight:600;color:{{ $props['title_color'] }};">
                                @if($item['url'])
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" style="color:{{ $props['title_color'] }};text-decoration:none;">{{ $item['title'] }}</a>
                                @else
                                    {{ $item['title'] }}
                                @endif
                            </p>
                            @if($item['meta'])
                                <p class="m-muted" style="margin:3px 0 0;font-size:13px;line-height:20px;color:{{ $props['meta_color'] }};">{{ $item['meta'] }}</p>
                            @endif
                        </td>
                        <td valign="top" align="right" style="padding:14px 0;border-bottom:1px solid {{ $props['border_color'] }};font-family:{{ $t['font.heading'] }};font-size:15px;line-height:22px;font-weight:600;color:{{ $props['title_color'] }};font-variant-numeric:tabular-nums;white-space:nowrap;">{{ $item['price'] }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
