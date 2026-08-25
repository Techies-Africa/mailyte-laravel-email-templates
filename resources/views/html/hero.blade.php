<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:0 0 {{ $props['space_below'] }};">
            @if($props['image'])
                <img src="{{ $props['image'] }}" alt="{{ $props['image_alt'] }}" width="552" style="display:block;width:100%;max-width:552px;height:auto;border:0;border-radius:{{ $props['radius'] }};margin:0 0 20px;">
                <div align="{{ $props['align'] }}">
                @if($props['eyebrow'])
                    <p style="margin:0 0 8px;font-family:{{ $t['font.body'] }};font-size:12px;line-height:16px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $props['accent_color'] }};">{{ $props['eyebrow'] }}</p>
                @endif
                <h1 class="m-h1" style="margin:0;font-family:{{ $t['font.heading'] }};font-size:{{ $props['h1']['size'] ?? '28px' }};line-height:{{ $props['h1']['line_height'] ?? '36px' }};font-weight:{{ $props['h1']['weight'] ?? '700' }};letter-spacing:{{ $props['h1']['letter_spacing'] ?? '0' }};color:{{ $props['text_color'] }};">{{ $props['title'] }}</h1>
                @if($props['subtitle'])
                    <p style="margin:10px 0 0;font-family:{{ $t['font.body'] }};font-size:16px;line-height:24px;color:{{ $props['muted_color'] }};">{{ $props['subtitle'] }}</p>
                @endif
                </div>
            @else
                {{-- No image: fall back to the theme's header gradient with a solid
                     bgcolor fallback, since Outlook honours the attribute but ignores
                     CSS gradients entirely. --}}
                <!--[if mso]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:552px;"><v:fill color="{{ $props['fallback_color'] }}" /><v:textbox inset="0,0,0,0"><![endif]-->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;@if($props['gradient'])background:{{ $props['gradient'] }};@endif border-radius:{{ $props['radius'] }};">
                    <tr>
                        <td align="{{ $props['align'] }}" bgcolor="{{ $props['fallback_color'] }}" style="padding:40px 32px;background-color:{{ $props['fallback_color'] }};border-radius:{{ $props['radius'] }};">
                            @if($props['eyebrow'])
                                <p style="margin:0 0 8px;font-family:{{ $t['font.body'] }};font-size:12px;line-height:16px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $props['accent_color'] }};">{{ $props['eyebrow'] }}</p>
                            @endif
                            <h1 class="m-h1" style="margin:0;font-family:{{ $t['font.heading'] }};font-size:{{ $props['h1']['size'] ?? '28px' }};line-height:{{ $props['h1']['line_height'] ?? '36px' }};font-weight:{{ $props['h1']['weight'] ?? '700' }};letter-spacing:{{ $props['h1']['letter_spacing'] ?? '0' }};color:{{ $props['text_color'] }};">{{ $props['title'] }}</h1>
                            @if($props['subtitle'])
                                <p style="margin:10px 0 0;font-family:{{ $t['font.body'] }};font-size:16px;line-height:24px;color:{{ $props['muted_color'] }};">{{ $props['subtitle'] }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
                <!--[if mso]></v:textbox></v:rect><![endif]-->
            @endif
        </td>
    </tr>
</table>
