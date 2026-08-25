@if($props['eyebrow'])
    <p style="margin:0 0 6px;font-family:{{ $t['font.heading'] }};font-size:11px;line-height:16px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:{{ $props['accent_color'] }};">{{ $props['eyebrow'] }}</p>
@endif
@if($props['title'])
    <p style="margin:0 0 6px;font-family:{{ $t['font.heading'] }};font-size:18px;line-height:26px;font-weight:600;letter-spacing:-0.01em;color:{{ $props['title_color'] }};">{{ $props['title'] }}</p>
@endif
@if($props['text'])
    <p class="m-muted" style="margin:0;font-family:{{ $t['font.body'] }};font-size:14px;line-height:23px;color:{{ $props['text_color'] }};">{{ $props['text'] }}</p>
@endif
@if($props['link_label'] && $props['link_url'])
    <p style="margin:10px 0 0;font-family:{{ $t['font.heading'] }};font-size:13px;line-height:20px;font-weight:600;">
        <a href="{{ $props['link_url'] }}" target="_blank" rel="noopener" style="color:{{ $props['accent_color'] }};text-decoration:none;">{{ $props['link_label'] }} &rarr;</a>
    </p>
@endif
