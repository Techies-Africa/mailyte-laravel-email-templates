@php($w = $props['width'])
@php($h = $props['height'])
@php($pad = $props['align'] === 'center' ? '36px '.$props['gutter'] : '36px '.$props['gutter'])
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:0 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td
                        @if($props['image']) background="{{ $props['image'] }}" @endif
                        bgcolor="{{ $props['fallback_color'] }}"
                        valign="middle"
                        align="{{ $props['align'] }}"
                        style="background-color:{{ $props['fallback_color'] }};@if($props['image'])background-image:url('{{ $props['image'] }}');background-size:cover;background-position:center;@endif border-radius:{{ $props['radius'] }};"
                    >
                        {{-- Outlook: draw the same picture as a VML rect, then place the
                             content inside it so there is only ever one copy of the text. --}}
                        <!--[if gte mso 9]>
                        <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:{{ $w }}px;height:{{ $h }}px;">
                            <v:fill @if($props['image']) type="frame" src="{{ $props['image'] }}" @endif color="{{ $props['fallback_color'] }}" />
                            <v:textbox inset="0,0,0,0">
                        <![endif]-->
                        <div>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                                <tr>
                                    <td align="{{ $props['align'] }}" height="{{ $h }}" style="height:{{ $h }}px;padding:{{ $pad }};background-color:{{ $props['scrim'] }};border-radius:{{ $props['radius'] }};">
                                        @if($props['eyebrow'])
                                            <p style="margin:0 0 10px;font-family:{{ $t['font.heading'] }};font-size:12px;line-height:16px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:{{ $props['text_color'] }};">{{ $props['eyebrow'] }}</p>
                                        @endif
                                        @if($props['title'])
                                            <h1 class="m-h1" style="margin:0;font-family:{{ $t['font.heading'] }};font-size:{{ $props['h1']['size'] ?? '30px' }};line-height:{{ $props['h1']['line_height'] ?? '38px' }};font-weight:{{ $props['h1']['weight'] ?? '700' }};letter-spacing:-0.02em;color:{{ $props['text_color'] }};">{{ $props['title'] }}</h1>
                                        @endif
                                        @if($props['subtitle'])
                                            <p style="margin:12px 0 0;font-family:{{ $t['font.body'] }};font-size:16px;line-height:26px;color:{{ $props['text_color'] }};">{{ $props['subtitle'] }}</p>
                                        @endif
                                        @if($props['button_label'] && $props['button_url'])
                                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:separate;margin:22px 0 0;">
                                                <tr>
                                                    <td align="center" bgcolor="{{ $props['button_bg'] }}" style="border-radius:{{ $props['button_radius'] }};">
                                                        <a class="m-btn" href="{{ $props['button_url'] }}" target="_blank" rel="noopener" style="display:inline-block;padding:13px 26px;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:20px;font-weight:600;color:{{ $props['button_color'] }};text-decoration:none;border-radius:{{ $props['button_radius'] }};">{{ $props['button_label'] }}</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!--[if gte mso 9]>
                            </v:textbox>
                        </v:rect>
                        <![endif]-->
                    </td>
                </tr>
            </table>
            @if($props['image'] && $props['image_alt'])
                {{-- The photo carries meaning only when it is decorative-with-a-caption;
                     when it is not, the alt text lives on this hidden image so screen
                     readers and image-blocked clients still get it. --}}
                <img src="{{ $props['image'] }}" alt="{{ $props['image_alt'] }}" width="1" height="1" style="display:none;width:1px;height:1px;max-height:0;overflow:hidden;opacity:0;">
            @endif
        </td>
    </tr>
</table>
