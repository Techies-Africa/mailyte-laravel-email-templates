<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td @class(['m-card' => $props['tone'] === 'soft']) align="{{ $props['align'] }}"
                        style="padding:{{ $props['tone'] === 'plain' ? '0' : '13px 16px' }};@if($props['background'] !== 'transparent')background-color:{{ $props['background'] }};@endif @if($props['border_color'])border:1px solid {{ $props['border_color'] }};@endif border-radius:{{ $props['radius'] }};">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;@if($props['align'] === 'center')margin:0 auto;@endif">
                            <tr>
                                @if($props['icon_url'])
                                    <td valign="middle" width="20" style="width:20px;padding:0 10px 0 0;">
                                        <img src="{{ $props['icon_url'] }}" alt="" width="20" height="20" style="display:block;border:0;width:20px;height:20px;">
                                    </td>
                                @elseif($props['mark'])
                                    <td valign="middle" style="padding:0 10px 0 0;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:22px;font-weight:700;color:{{ $props['mark_color'] }};">{{ $props['mark'] }}</td>
                                @endif
                                <td valign="middle" style="font-family:{{ $t['font.body'] }};font-size:{{ $t['type.small.size'] ?? '14px' }};line-height:{{ $t['type.small.line_height'] ?? '22px' }};color:{{ $props['text_color'] }};">
                                    @if($props['strong_text'])
                                        <strong style="font-weight:700;">{{ $props['strong_text'] }}</strong>
                                    @endif
                                    {{ $props['text'] }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
