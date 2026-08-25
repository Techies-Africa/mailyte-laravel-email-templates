{{-- Bulletproof button. Square corners are accepted in classic Outlook rather
     than reaching for VML RoundRect; the mso-padding-alt/letter-spacing pair is
     the standard fix for Outlook ignoring padding on an anchor. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td align="{{ $props['align'] }}" style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" @if($props['full_width']) width="100%" @endif style="border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;">
                <tr>
                    <td align="center" @if(in_array($props['variant'], ['primary', 'danger'], true)) class="{{ $props['variant'] === 'danger' ? 'm-btn-danger' : 'm-btn-plate' }}" @endif @if(! $props['bare']) bgcolor="{{ $props['background'] }}" @endif style="border-radius:{{ $props['radius'] }};@if($props['border'])border:1px solid {{ $props['border'] }};@endif @if($props['shadow'] && ! $props['bare'])box-shadow:{{ $props['shadow'] }};@endif">
                        {{-- m-btn drives the dark-mode colour swap, which assumes a filled
                             plate behind the label. An outline or link button has no plate,
                             so wearing that class would repaint its text in the filled
                             button's ink and make it invisible on a dark surface. --}}
                        <a @if(! $props['bare']) class="m-btn @if($props['variant'] === 'primary')m-btn-plate @elseif($props['variant'] === 'danger')m-btn-danger @endif" @endif href="{{ $props['url'] }}" target="_blank" rel="noopener" style="display:inline-block;@if($props['full_width'])width:100%;box-sizing:border-box;@endif padding:{{ $props['variant'] === 'link' ? '0' : $props['padding_y'].' '.$props['padding_x'] }};@if(! $props['bare'])background-color:{{ $props['background'] }};@endif border-radius:{{ $props['radius'] }};font-family:{{ $t['font.body'] }};font-size:{{ $props['type']['size'] ?? '16px' }};line-height:{{ $props['type']['line_height'] ?? '16px' }};font-weight:{{ $props['type']['weight'] ?? '600' }};letter-spacing:{{ $props['type']['letter_spacing'] ?? '0' }};text-transform:{{ $props['type']['transform'] ?? 'none' }};color:{{ $props['color'] }};text-decoration:{{ $props['underline'] ? 'underline' : 'none' }};text-align:center;mso-padding-alt:0;mso-line-height-rule:exactly;">
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
