<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    {{-- Cheap to include, low expected payoff: caniemail measures the meta tag
         at under 5% support. Ship it, expect nothing from it. --}}
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title ?? '' }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <style>
        table, td, div, h1, h2, h3, p, a { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style>
{!! $css !!}
    </style>
</head>
<body class="m-body" style="margin:0;padding:0;width:100%;background-color:{{ $t['color.bg'] }};">
{!! $preheaderHtml ?? '' !!}
<div role="article" aria-roledescription="email" aria-label="{{ $title ?? '' }}" lang="{{ $locale ?? 'en' }}">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="m-body" style="border-collapse:collapse;background-color:{{ $t['color.bg'] }};">
    <tr>
        <td align="center" style="padding:{{ $t['layout.outer_padding'] ?? '32px' }} 12px;">
            <!--[if mso | IE]>
            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="{{ (int) str_replace('px', '', (string) ($t['layout.width'] ?? '600px')) }}"><tr><td>
            <![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="m-canvas" style="border-collapse:collapse;width:100%;max-width:{{ $t['layout.width'] ?? '600px' }};margin:0 auto;background-color:{{ $t['color.surface'] }};border-radius:{{ $t['radius.lg'] ?? '10px' }};">
                <tr>
                    <td class="m-gutter" style="padding:{{ $t['layout.gutter'] ?? '24px' }} {{ $t['layout.gutter'] ?? '24px' }} 8px;">
                        @yield('header')
                    </td>
                </tr>
                <tr>
                    {{-- No horizontal padding here on purpose: each block applies the
                         gutter itself (see BlockRegistry::render), so a full-bleed
                         section can reach the edges of the canvas while ordinary
                         blocks stay inside the measure. --}}
                    <td style="padding:0;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td class="m-gutter" style="padding:8px {{ $t['layout.gutter'] ?? '24px' }} {{ $t['layout.gutter'] ?? '24px' }};">
                        @yield('footer')
                    </td>
                </tr>
            </table>
            <!--[if mso | IE]></td></tr></table><![endif]-->
        </td>
    </tr>
</table>
</div>
</body>
</html>
