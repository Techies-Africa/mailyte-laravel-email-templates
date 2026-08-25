{{-- Every section here is independently switchable, because a receipt, a
     security alert and a newsletter do not owe the reader the same closing
     matter. `branded` turns the full set on; `plain` strips it back to what the
     law and the mail client need. --}}
@php($align = $footerAlign ?? 'left')
{{-- Precedence: a template's own token wins, otherwise the layout's default.
     `branded` asks for the full set, and a bundle that genuinely should not
     carry a section sets the token to false in its design.json. --}}
@php($showSocial = ($t['footer.show_social'] ?? $showSocial ?? true) && ! empty($t['social']))
@php($showAddress = $t['footer.show_address'] ?? $showAddress ?? true)
@php($showCopyright = $t['footer.show_copyright'] ?? $showCopyright ?? true)
@php($showReason = $t['footer.show_reason'] ?? $showReason ?? true)
{{-- Parameter-driven: unset means "show it when there is a URL to show", which
     is the sane default; an explicit true or false overrides that either way. --}}
@php($showUnsubscribe = $t['footer.show_unsubscribe'] ?? $showUnsubscribe ?? null)
@php($address = $t['footer.address'] ?? ($globals['company']['address'] ?? null))
@php($companyName = $globals['company']['name'] ?? ($globals['product']['name'] ?? ''))
@php($copyright = $t['footer.copyright'] ?? ($companyName !== '' ? '© '.date('Y').' '.$companyName : null))
@php($unsubscribe = $globals['unsubscribe_url'] ?? null)
@php($preferences = $globals['preferences_url'] ?? null)

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:20px 0 0;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr><td class="m-divider" height="1" style="height:1px;line-height:1px;font-size:0;background-color:{{ $t['color.border'] }};">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    @if($showSocial)
        <tr>
            <td style="padding:18px 0 0;">
                @include('mailyte::html.partials.social', ['socialAlign' => $align])
            </td>
        </tr>
    @endif

    <tr>
        <td class="m-muted" align="{{ $align }}" style="padding:16px 0 0;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.footer.size'] ?? '13px' }};line-height:{{ $t['type.footer.line_height'] ?? '20px' }};color:{{ $t['color.text_muted'] }};">
            @if($showReason && ($t['footer.reason'] ?? null))
                <p style="margin:0 0 8px;">{{ $t['footer.reason'] }}</p>
            @endif

            @if(($t['footer.legal'] ?? null))
                <p style="margin:0 0 8px;">{{ $t['footer.legal'] }}</p>
            @endif

            @if($showAddress && $address)
                <p style="margin:0 0 8px;">{{ $address }}</p>
            @endif

            @if($showCopyright && $copyright)
                <p style="margin:0;color:{{ $t['color.text_muted'] }};">{{ $copyright }}</p>
            @endif
        </td>
    </tr>

    @php($unsubscribeVisible = $showUnsubscribe === true || ($showUnsubscribe === null && ($unsubscribe || $preferences)))
    @if($unsubscribeVisible)
        {{-- Last line of the message on purpose: it is where every mail client
             trains people to look for it, and burying it above the address is
             what earns a spam complaint instead of an unsubscribe. --}}
        <tr>
            <td class="m-muted" align="{{ $align }}" style="padding:14px 0 0;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.footer.size'] ?? '13px' }};line-height:{{ $t['type.footer.line_height'] ?? '20px' }};color:{{ $t['color.text_muted'] }};">
                @if(($t['footer.unsubscribe_note'] ?? null))
                    <p style="margin:0 0 6px;">{{ $t['footer.unsubscribe_note'] }}</p>
                @endif
                <p style="margin:0;">
                    @if($unsubscribe)
                        <a href="{{ $unsubscribe }}" style="color:{{ $t['color.text_muted'] }};text-decoration:underline;">{{ $t['footer.unsubscribe_text'] ?? 'Unsubscribe' }}</a>
                    @endif
                    @if($unsubscribe && $preferences) &nbsp;&middot;&nbsp; @endif
                    @if($preferences)
                        <a href="{{ $preferences }}" style="color:{{ $t['color.text_muted'] }};text-decoration:underline;">{{ $t['footer.preferences_text'] ?? 'Email preferences' }}</a>
                    @endif
                </p>
            </td>
        </tr>
    @endif
</table>
