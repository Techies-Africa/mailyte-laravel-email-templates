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
                                @if(($t['header.show_name'] ?? false) && $productName !== '')
                                    {{-- The name as TEXT beside the mark, which is not
                                         decoration: most clients block images by default, and
                                         a logo-only header then arrives as an empty box with
                                         nothing saying who sent it. Text survives that.

                                         A shrink-wrapped table carrying `align` rather than a
                                         full-width one: the pair has to centre as a UNIT, and a
                                         100%-wide table would centre each half in its own column
                                         instead, leaving the mark and the name far apart. It is
                                         also the only construction Outlook honours -- no
                                         inline-block, no flex.

                                         The two cells stack below 480px, where a wide mark and a
                                         long name together would otherwise run past the canvas. --}}
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="{{ $align }}" style="border-collapse:collapse;">
                                        <tr>
                                            <td class="m-stack" style="vertical-align:middle;">
                                                @include('mailyte::html.partials.logo-mark', ['lockup' => true])
                                            </td>
                                            {{-- The gutter is padding here and nothing on mobile:
                                                 a stacked cell is content-box, so padding-left
                                                 would be added OUTSIDE its 100% width and show up
                                                 as a sideways scroll. m-lockup-name zeroes it and
                                                 puts the gap above instead. --}}
                                            <td class="m-lockup-name" style="vertical-align:middle;padding-left:12px;">
                                                <span style="font-family:{{ $t['font.heading'] }};font-size:17px;line-height:24px;font-weight:700;letter-spacing:-0.01em;color:{{ $t['color.text'] }};">{{ $productName }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    @include('mailyte::html.partials.logo-mark')
                                @endif
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
