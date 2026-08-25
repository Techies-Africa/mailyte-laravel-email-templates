<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td @class(['m-card' => $props['tone'] === 'light']) bgcolor="{{ $props['background'] }}" align="center" style="background-color:{{ $props['background'] }};border-radius:{{ $props['radius'] }};padding:32px 24px;">
                        @if($props['eyebrow'])
                            <p style="margin:0 0 10px;font-family:{{ $t['font.heading'] }};font-size:11px;line-height:16px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:{{ $props['muted_ink'] }};">{{ $props['eyebrow'] }}</p>
                        @endif

                        @if($props['headline'])
                            <p style="margin:0;font-family:{{ $t['font.heading'] }};font-size:34px;line-height:40px;font-weight:800;letter-spacing:-0.03em;color:{{ $props['ink'] }};">{{ $props['headline'] }}</p>
                        @endif

                        @if($props['text'])
                            <p style="margin:12px 0 0;font-family:{{ $t['font.body'] }};font-size:15px;line-height:24px;color:{{ $props['muted_ink'] }};">{{ $props['text'] }}</p>
                        @endif

                        @if($props['code'])
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:20px auto 0;">
                                <tr>
                                    <td align="center" style="padding:10px 18px;border:1px dashed {{ $props['chip_border'] }};border-radius:8px;">
                                        <span style="font-family:{{ $t['font.body'] }};font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:{{ $props['muted_ink'] }};">{{ $props['code_label'] }}</span>
                                        <span style="font-family:{{ $props['mono_font'] }};font-size:18px;line-height:24px;font-weight:700;letter-spacing:.10em;color:{{ $props['ink'] }};">&nbsp;{{ $props['code'] }}</span>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if($props['button_label'] && $props['button_url'])
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:separate;margin:20px auto 0;">
                                <tr>
                                    <td align="center" bgcolor="{{ $props['button_bg'] }}" style="border-radius:{{ $props['button_radius'] }};">
                                        <a href="{{ $props['button_url'] }}" target="_blank" rel="noopener" style="display:inline-block;padding:14px 30px;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:20px;font-weight:700;color:{{ $props['button_ink'] }};text-decoration:none;border-radius:{{ $props['button_radius'] }};">{{ $props['button_label'] }}</a>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if($props['expires'])
                            <p style="margin:16px 0 0;font-family:{{ $t['font.body'] }};font-size:13px;line-height:20px;font-weight:600;color:{{ $props['ink'] }};">{{ $props['expires'] }}</p>
                        @endif
                    </td>
                </tr>
            </table>

            @if($props['terms'])
                <p class="m-muted" style="margin:10px 0 0;font-family:{{ $t['font.body'] }};font-size:12px;line-height:18px;color:{{ $t['color.text_muted'] }};">{{ $props['terms'] }}</p>
            @endif
        </td>
    </tr>
</table>
