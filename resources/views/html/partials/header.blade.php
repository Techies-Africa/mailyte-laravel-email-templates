{{-- The leader: an optional banner image, an optional logo, and where the logo
     sits. All four combinations are legitimate and templates use all of them --
     banner with a logo over it, banner with the logo beneath, logo alone, or
     nothing at all for the plainest security mail.

     Alignment resolves layout override -> header.align -> logo.align -> left,
     so a layout can impose a house rule while a template's design.json still
     gets the last word through its tokens. --}}
@php($align = $logoAlign ?? $t['header.align'] ?? $t['logo.align'] ?? 'left')
@php($showLogo = $t['header.show_logo'] ?? $showLogo ?? true)
@php($banner = $t['header.banner_url'] ?? null)
@php($logoOnBanner = $banner && ($t['header.logo_on_banner'] ?? false))
@php($gradient = $t['header.gradient'] ?? null)
@php($accentBar = $t['header.accent_bar'] ?? null)
@php($fallback = $t['color.surface_alt'] ?? $t['color.surface'])
@php($productName = $globals['product']['name'] ?? '')
@php($productUrl = $globals['product']['url'] ?? '#')
@php($bannerLink = $t['header.banner_url_link'] ?? $productUrl)

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    @if($accentBar)
        <tr>
            <td height="3" style="height:3px;line-height:3px;font-size:0;background:{{ $accentBar }};background-color:{{ $t['color.primary'] }};border-radius:{{ $t['radius.lg'] ?? '10px' }} {{ $t['radius.lg'] ?? '10px' }} 0 0;">&nbsp;</td>
        </tr>
    @endif

    @if($banner)
        <tr>
            <td style="padding:0 0 {{ $logoOnBanner ? '0' : '18px' }};">
                @if($logoOnBanner)
                    {{-- Logo sitting on the banner. The plate behind it is solid, not a
                         gradient: Outlook drops gradient overlays and a logo stranded on
                         a busy photo is unreadable in exactly the clients that matter. --}}
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                        <tr>
                            <td background="{{ $banner }}" bgcolor="{{ $fallback }}" valign="middle" align="{{ $align }}" height="{{ $t['header.banner_height'] ?? '180' }}"
                                style="background-color:{{ $fallback }};background-image:url('{{ $banner }}');background-size:cover;background-position:center;height:{{ $t['header.banner_height'] ?? '180' }}px;padding:20px;border-radius:{{ $t['radius.lg'] ?? '10px' }};">
                                <!--[if gte mso 9]>
                                <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:{{ (int) str_replace('px','',(string) ($t['layout.width'] ?? '600px')) }}px;height:{{ $t['header.banner_height'] ?? '180' }}px;">
                                    <v:fill type="frame" src="{{ $banner }}" color="{{ $fallback }}" />
                                    <v:textbox inset="0,0,0,0">
                                <![endif]-->
                                <div>
                                    @if($showLogo && ($t['logo.url'] ?? null))
                                        <a href="{{ $productUrl }}" target="_blank" rel="noopener">
                                            <img class="m-logo" src="{{ $t['logo.url'] }}" alt="{{ $t['logo.alt'] ?: $productName }}" width="{{ $t['logo.width'] ?? '140' }}" style="display:inline-block;border:0;width:100%;max-width:{{ $t['logo.width'] ?? '140' }}px;height:auto;">
                                        </a>
                                    @endif
                                </div>
                                <!--[if gte mso 9]>
                                    </v:textbox>
                                </v:rect>
                                <![endif]-->
                            </td>
                        </tr>
                    </table>
                @else
                    <a href="{{ $bannerLink }}" target="_blank" rel="noopener">
                        <img src="{{ $banner }}" alt="{{ $t['header.banner_alt'] ?? '' }}" width="{{ (int) str_replace('px','',(string) ($t['layout.width'] ?? '600px')) }}" style="display:block;border:0;width:100%;height:auto;border-radius:{{ $t['radius.lg'] ?? '10px' }};">
                    </a>
                @endif
            </td>
        </tr>
    @endif

    @if($showLogo && ! $logoOnBanner)
        <tr>
            <td @if($gradient && ! $banner) bgcolor="{{ $fallback }}" style="background:{{ $gradient }};background-color:{{ $fallback }};padding:22px {{ $t['layout.gutter'] ?? '24px' }};" @else style="padding:{{ $banner ? '0' : '8px' }} 0 16px;" @endif>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td align="{{ $align }}">
                            @if(($t['logo.url'] ?? null))
                                {{-- A transparent logo with dark artwork disappears on an
                                     inverted background, so themes should ship a mark with
                                     a baked-in stroke or plate rather than relying on a
                                     prefers-color-scheme swap that only Apple Mail honours. --}}
                                <a href="{{ $productUrl }}" target="_blank" rel="noopener">
                                    <img class="m-logo m-logo-light" src="{{ $t['logo.url'] }}" alt="{{ $t['logo.alt'] ?: $productName }}" width="{{ $t['logo.width'] ?? '140' }}" style="display:block;border:0;width:100%;max-width:{{ $t['logo.width'] ?? '140' }}px;height:auto;margin:{{ $align === 'center' ? '0 auto' : ($align === 'right' ? '0 0 0 auto' : '0') }};">
                                    @if(($t['logo.dark_url'] ?? null))
                                        {{-- Swapped in by the dark-mode stylesheet. Hidden rather than
                                             absent so clients that ignore the media query never show
                                             two marks. --}}
                                        <img class="m-logo m-logo-dark" src="{{ $t['logo.dark_url'] }}" alt="{{ $t['logo.alt'] ?: $productName }}" width="{{ $t['logo.width'] ?? '140' }}" style="display:none;border:0;width:100%;max-width:{{ $t['logo.width'] ?? '140' }}px;height:auto;margin:{{ $align === 'center' ? '0 auto' : ($align === 'right' ? '0 0 0 auto' : '0') }};mso-hide:all;">
                                    @endif
                                </a>
                            @elseif($productName !== '')
                                <span style="font-family:{{ $t['font.heading'] }};font-size:17px;line-height:24px;font-weight:700;letter-spacing:-0.01em;color:{{ $t['color.text'] }};">{{ $productName }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif
</table>
