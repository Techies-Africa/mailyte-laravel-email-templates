{{-- Bulletproof button. Square corners are accepted in classic Outlook rather
     than reaching for VML RoundRect; the mso-padding-alt/letter-spacing pair is
     the standard fix for Outlook ignoring padding on an anchor. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td align="{{ $props['align'] }}" style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" @if($props['full_width']) width="100%" @endif style="border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;">
                <tr>
                    <td align="center" bgcolor="{{ $props['background'] }}" style="border-radius:{{ $props['radius'] }};">
                        <a class="m-btn" href="{{ $props['url'] }}" target="_blank" rel="noopener" style="display:inline-block;@if($props['full_width'])width:100%;@endif padding:{{ $props['padding_y'] }} {{ $props['padding_x'] }};background-color:{{ $props['background'] }};border-radius:{{ $props['radius'] }};font-family:{{ $t['font.body'] }};font-size:{{ $props['type']['size'] ?? '16px' }};line-height:{{ $props['type']['line_height'] ?? '16px' }};font-weight:{{ $props['type']['weight'] ?? '600' }};letter-spacing:{{ $props['type']['letter_spacing'] ?? '0' }};text-transform:{{ $props['type']['transform'] ?? 'none' }};color:{{ $props['color'] }};text-decoration:none;text-align:center;mso-padding-alt:0;mso-line-height-rule:exactly;">
                            <!--[if mso]><i style="mso-font-width:-100%;mso-text-raise:{{ $props['padding_y'] }}" hidden>&nbsp;</i><![endif]-->
                            <span style="mso-text-raise:6px;">{{ $props['label'] }}</span>
                            <!--[if mso]><i style="mso-font-width:-100%" hidden>&nbsp;</i><![endif]-->
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@if($props['fallback_text'] !== '' && $props['url'])
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:0 0 {{ $props['space_below'] }};">
            <p style="margin:0;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.small.size'] }};line-height:{{ $t['type.small.line_height'] }};color:{{ $t['color.text_muted'] }};">{{ $props['fallback_text'] }}</p>
            <p style="margin:8px 0 0;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.small.size'] }};line-height:{{ $t['type.small.line_height'] }};word-break:break-all;"><a href="{{ $props['url'] }}" target="_blank" rel="noopener" style="color:{{ $t['color.link'] }};text-decoration:underline;">{{ $props['url'] }}</a></p>
        </td>
    </tr>
</table>
@endif
