{{-- Ghost table for Outlook, percentage cells everywhere else, stacking under
     600px via .m-stack. The image cell keeps its own background so a blocked
     image leaves a coloured panel rather than a white hole. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td @class([
                'm-alt' => in_array($props['tone'], ['alt', 'custom'], true),
                'm-hold' => in_array($props['tone'], ['dark', 'accent'], true),
            ]) bgcolor="{{ $props['background'] }}" style="background-color:{{ $props['background'] }};padding:0 0 {{ $props['space_below'] }};">
            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
                <td valign="middle" width="{{ $props['reverse'] ? $props['image_cell'] : $props['text_cell'] }}">
                <td valign="middle" width="{{ $props['reverse'] ? $props['text_cell'] : $props['image_cell'] }}">
            <![endif]-->
            <!--[if !mso]><!-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;"><tr>
            <!--<![endif]-->

            @if($props['reverse'] && $props['image'])
                <td class="m-stack" valign="middle" width="{{ $props['image_percent'] }}%" style="width:{{ $props['image_percent'] }}%;">
                    <img class="m-img-fill" src="{{ $props['image'] }}" alt="{{ $props['image_alt'] }}" width="{{ $props['image_cell'] }}" style="display:block;border:0;width:100%;max-width:{{ $props['image_cell'] }}px;height:auto;">
                </td>
            @endif

            <td class="m-stack" valign="middle" width="{{ $props['image'] ? $props['text_percent'] : 100 }}%" style="width:{{ $props['image'] ? $props['text_percent'] : 100 }}%;padding:{{ $props['padding'] }};">
                @if($props['eyebrow'])
                    <p style="margin:0 0 10px;font-family:{{ $t['font.heading'] }};font-size:11px;line-height:16px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:{{ $props['muted_ink'] }};">{{ $props['eyebrow'] }}</p>
                @endif
                @if($props['title'])
                    <h1 class="m-h1" style="margin:0;font-family:{{ $t['font.heading'] }};font-size:{{ $props['h1']['size'] ?? '30px' }};line-height:{{ $props['h1']['line_height'] ?? '38px' }};font-weight:{{ $props['h1']['weight'] ?? '700' }};letter-spacing:{{ $props['h1']['letter_spacing'] ?? '-0.02em' }};color:{{ $props['ink'] }};">{{ $props['title'] }}</h1>
                @endif
                @if($props['text'])
                    <p style="margin:12px 0 0;font-family:{{ $t['font.body'] }};font-size:15px;line-height:25px;color:{{ $props['muted_ink'] }};">{{ $props['text'] }}</p>
                @endif
                @if($props['button_label'] && $props['button_url'])
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:separate;margin:20px 0 0;">
                        <tr>
                            <td class="m-btn-plate" align="center" bgcolor="{{ $props['button_bg'] }}" style="border-radius:{{ $props['button_radius'] }};">
                                <a class="m-btn m-btn-plate" href="{{ $props['button_url'] }}" target="_blank" rel="noopener" style="display:inline-block;padding:13px 26px;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:20px;font-weight:600;color:{{ $props['button_ink'] }};text-decoration:none;border-radius:{{ $props['button_radius'] }};">{{ $props['button_label'] }}</a>
                            </td>
                        </tr>
                    </table>
                @endif
            </td>

            @if(! $props['reverse'] && $props['image'])
                <td class="m-stack" valign="middle" width="{{ $props['image_percent'] }}%" style="width:{{ $props['image_percent'] }}%;">
                    <img class="m-img-fill" src="{{ $props['image'] }}" alt="{{ $props['image_alt'] }}" width="{{ $props['image_cell'] }}" style="display:block;border:0;width:100%;max-width:{{ $props['image_cell'] }}px;height:auto;">
                </td>
            @endif

            <!--[if !mso]><!-->
            </tr></table>
            <!--<![endif]-->
            <!--[if mso]>
            </tr></table>
            <![endif]-->
        </td>
    </tr>
</table>
