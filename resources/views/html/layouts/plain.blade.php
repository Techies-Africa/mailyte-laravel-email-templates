{{-- Near-plaintext HTML: no logo, no surface card, no imagery.
     The safest thing to send for security-critical mail, and the layout to
     reach for when deliverability matters more than presentation. --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ $locale ?? 'en' }}" dir="{{ $direction ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title ?? '' }}</title>
    <style>
{!! $css !!}
    </style>
</head>
<body class="m-body" style="margin:0;padding:0;width:100%;background-color:{{ $t['color.bg'] }};">
{!! $preheaderHtml ?? '' !!}
<div role="article" aria-roledescription="email" aria-label="{{ $title ?? '' }}" lang="{{ $locale ?? 'en' }}">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="m-body" style="border-collapse:collapse;background-color:{{ $t['color.bg'] }};">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <!--[if mso | IE]><table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="600"><tr><td><![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="m-canvas" style="border-collapse:collapse;width:100%;max-width:{{ $t['layout.width'] ?? '600px' }};margin:0 auto;background-color:{{ $t['color.bg'] }};">
                <tr>
                    <td style="padding:0;">
                        {!! $content !!}

                        {{-- The gutter lives on the blocks, so the footer -- which is not a
                             block -- has to bring its own or it sits flush against the
                             canvas edge while the body above it does not. --}}
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                            <tr>
                                <td class="m-gutter" style="padding:0 {{ $t['layout.gutter'] ?? '24px' }};">
                                    @include('mailyte::html.partials.footer', [
                                        'showSocial' => false,
                                        'showCopyright' => false,
                                    ])
                                </td>
                            </tr>
                        </table>
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
