@php($fontSize = $props['size'] === 'large' ? '22px' : '17px')
@php($lineHeight = $props['size'] === 'large' ? '32px' : '27px')
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    @if($props['align'] === 'left')
                        <td width="3" style="width:3px;background-color:{{ $props['accent_color'] }};border-radius:2px;">&nbsp;</td>
                    @endif
                    <td class="m-card m-quote" align="{{ $props['align'] }}" style="padding:{{ $props['align'] === 'left' ? '2px 0 2px 18px' : '4px 12px' }};@if($props['background'] !== 'transparent')background-color:{{ $props['background'] }};border-radius:{{ $props['radius'] }};padding:20px;@endif">
                        <blockquote style="margin:0;font-family:{{ $props['font_heading'] }};font-size:{{ $fontSize }};line-height:{{ $lineHeight }};font-weight:500;letter-spacing:-0.01em;color:{{ $props['text_color'] }};">{{ $props['text'] }}</blockquote>

                        @if($props['author'] || $props['avatar'])
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:14px 0 0;@if($props['align'] === 'center')display:inline-table;@endif">
                                <tr>
                                    @if($props['avatar'])
                                        <td valign="middle" width="36" style="width:36px;padding:0 10px 0 0;">
                                            <img src="{{ $props['avatar'] }}" alt="" width="36" height="36" style="display:block;border:0;width:36px;height:36px;border-radius:50%;">
                                        </td>
                                    @endif
                                    <td valign="middle" align="left">
                                        @if($props['author'])
                                            <p style="margin:0;font-family:{{ $props['font_heading'] }};font-size:13px;line-height:18px;font-weight:600;color:{{ $props['text_color'] }};">{{ $props['author'] }}</p>
                                        @endif
                                        @if($props['role'])
                                            <p class="m-muted" style="margin:1px 0 0;font-family:{{ $t['font.body'] }};font-size:12px;line-height:18px;color:{{ $props['muted_color'] }};">{{ $props['role'] }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
