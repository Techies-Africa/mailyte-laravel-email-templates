{{-- Social row. Brand glyphs are not in Unicode and SVG does not survive Gmail,
     so the default treatment is a bordered round cell carrying the platform's
     initial, which renders identically everywhere and degrades to a plain
     letter when images are blocked. Supply `icon_url` on an entry to use a real
     hosted icon instead; the alt text keeps it announceable either way. --}}
@php($style = $t['footer.social_style'] ?? 'round')
@php($align = $socialAlign ?? 'left')
@php($iconBase = rtrim((string) ($t['footer.social_icon_base'] ?? ''), '/'))

{{-- Ink follows the surface it sits on. A dark-stock template would otherwise
     get dark marks on a dark footer, which is invisible rather than subtle. An
     explicit `footer.social_icon_ink` still wins over the calculation. --}}
@php($surfaceHex = ltrim((string) ($t['color.surface'] ?? '#ffffff'), '#'))
@php($surfaceHex = strlen($surfaceHex) === 3 ? $surfaceHex[0].$surfaceHex[0].$surfaceHex[1].$surfaceHex[1].$surfaceHex[2].$surfaceHex[2] : substr($surfaceHex, 0, 6))
@php($rgb = strlen($surfaceHex) === 6 ? array_map('hexdec', str_split($surfaceHex, 2)) : [255, 255, 255])
@php($luma = (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255)
@php($iconInk = $t['footer.social_icon_ink'] ?? ($luma > 0.55 ? 'dark' : 'light'))
@php($iconInkOpposite = $iconInk === 'dark' ? 'light' : 'dark')
@php($iconSize = (int) ($t['footer.social_icon_size'] ?? 22))

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    @if(($t['footer.social_heading'] ?? '') !== '')
        <tr>
            <td align="{{ $align }}" class="m-muted" style="padding:0 0 10px;font-family:{{ $t['font.body'] }};font-size:11px;line-height:16px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:{{ $t['color.text_subtle'] ?? $t['color.text_muted'] }};">{{ $t['footer.social_heading'] }}</td>
        </tr>
    @endif
    <tr>
        <td align="{{ $align }}" style="padding:0;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    @foreach($t['social'] as $social)
                        @php($name = $social['name'] ?? '')
                        @php($url = $social['url'] ?? '#')
                        @php($label = $social['label'] ?? $name)
                        <td valign="middle" style="padding:0 {{ $loop->last ? '0' : '10px' }} 0 0;">
                            @php($iconUrl = $social['icon_url'] ?? ($iconBase !== '' ? $iconBase.'/'.\Illuminate\Support\Str::slug($name).'-'.$iconInk.'.png' : null))
                            @php($iconAlt = $social['icon_url'] ?? ($iconBase !== '' ? $iconBase.'/'.\Illuminate\Support\Str::slug($name).'-'.$iconInkOpposite.'.png' : null))
                            @if($iconUrl)
                                {{-- Real platform marks, sized at 2x and served from a public
                                     URL. The link is what carries the meaning for screen
                                     readers, so the alt text names the platform. The second
                                     copy is the opposite ink, swapped in by the dark-mode
                                     stylesheet in the clients that honour it. --}}
                                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" style="text-decoration:none;">
                                    <img class="m-social-light" src="{{ $iconUrl }}" alt="{{ $label }}" width="{{ $iconSize }}" height="{{ $iconSize }}" style="display:block;border:0;width:{{ $iconSize }}px;height:{{ $iconSize }}px;">
                                    @if($iconAlt && $iconAlt !== $iconUrl)
                                        <img class="m-social-dark" src="{{ $iconAlt }}" alt="{{ $label }}" width="{{ $iconSize }}" height="{{ $iconSize }}" style="display:none;border:0;width:{{ $iconSize }}px;height:{{ $iconSize }}px;mso-hide:all;">
                                    @endif
                                </a>
                            @elseif($style === 'text')
                                <a href="{{ $url }}" target="_blank" rel="noopener" style="font-family:{{ $t['font.body'] }};font-size:{{ $t['type.footer.size'] ?? '13px' }};line-height:20px;color:{{ $t['color.text_muted'] }};text-decoration:underline;">{{ $label }}</a>
                            @else
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td align="center" valign="middle" width="{{ $style === 'pill' ? 'auto' : '30' }}" height="30"
                                            style="width:{{ $style === 'pill' ? 'auto' : '30px' }};height:30px;padding:{{ $style === 'pill' ? '0 12px' : '0' }};border:1px solid {{ $t['color.border'] }};border-radius:{{ $style === 'pill' ? ($t['radius.pill'] ?? '999px') : '50%' }};">
                                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" style="display:block;font-family:{{ $t['font.heading'] }};font-size:{{ $style === 'pill' ? '12px' : '13px' }};line-height:30px;font-weight:700;color:{{ $t['color.text_muted'] }};text-decoration:none;">{{ $style === 'pill' ? $label : mb_strtoupper(mb_substr($name, 0, 1)) }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
