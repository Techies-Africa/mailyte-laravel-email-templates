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
                    @if($item['image'])
                        @if($item['url'])<a href="{{ $item['url'] }}" target="_blank" rel="noopener" style="text-decoration:none;">@endif
                        <img class="m-img-fill" src="{{ $item['image'] }}" alt="{{ $item['title'] }}" width="{{ $props['image_width'] }}" height="{{ $props['image_height'] }}" style="display:block;border:0;width:100%;max-width:{{ $props['image_width'] }}px;height:auto;border-radius:{{ $props['radius'] }};">
                        @if($item['url'])</a>@endif
                    @endif

                    @if($props['has_badges'])
                        {{-- Rendered for every cell once any cell has a badge, so the
                             titles below still line up across the row. --}}
                        <p style="margin:10px 0 0;height:22px;line-height:22px;">
                            @if($item['badge'])
                                <span class="m-alt" style="display:inline-block;padding:3px 8px;font-family:{{ $t['font.heading'] }};font-size:11px;line-height:16px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:{{ $props['accent_color'] }};background-color:{{ $props['badge_bg'] }};border-radius:99px;">{{ $item['badge'] }}</span>
                            @else
                                &nbsp;
                            @endif
                        </p>
                    @endif

                    @if($item['title'])
                        <p style="margin:10px 0 0;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:22px;font-weight:600;color:{{ $props['title_color'] }};">
                            @if($item['url'])
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener" style="color:{{ $props['title_color'] }};text-decoration:none;">{{ $item['title'] }}</a>
                            @else
                                {{ $item['title'] }}
                            @endif
                        </p>
                    @endif

                    @if($item['meta'])
                        <p class="m-muted" style="margin:3px 0 0;font-family:{{ $t['font.body'] }};font-size:13px;line-height:20px;color:{{ $props['meta_color'] }};">{{ $item['meta'] }}</p>
                    @endif

                    @if($item['price'])
                        <p style="margin:6px 0 0;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:22px;font-weight:700;color:{{ $props['price_color'] }};">
                            {{ $item['price'] }}
                            @if($item['was_price'])
                                <span style="font-weight:400;text-decoration:line-through;color:{{ $props['was_color'] }};">{{ $item['was_price'] }}</span>
                            @endif
                        </p>
                    @endif

                    @if($props['show_links'] && $item['url'])
                        <p style="margin:8px 0 0;font-family:{{ $t['font.heading'] }};font-size:13px;line-height:20px;font-weight:600;">
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener" style="color:{{ $props['accent_color'] }};text-decoration:none;">{{ $props['link_label'] }} &rarr;</a>
                        </p>
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
