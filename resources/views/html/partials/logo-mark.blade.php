{{--
    The linked logo, light and dark.

    Its own partial because the header draws it in two arrangements -- alone, or
    as the left half of a lockup with the company name beside it -- and the two
    branches must not carry separate copies of the dark-mode swap. They drifted
    once already.

    $lockup  true when the mark sits in a table cell next to the name. The
             alignment margin is the caller's job then, not the image's: inside
             a shrink-wrapped lockup an `auto` margin would push the mark away
             from the name it belongs to.
--}}
@php($lockup = $lockup ?? false)
@php($logoWidth = $t['logo.width'] ?? '140')
@php($logoMargin = $lockup ? '0' : ($align === 'center' ? '0 auto' : ($align === 'right' ? '0 0 0 auto' : '0')))

{{-- A transparent logo with dark artwork disappears on an inverted background,
     so themes should ship a mark with a baked-in stroke or plate rather than
     relying on a prefers-color-scheme swap that only Apple Mail honours. --}}
<a href="{{ $productUrl }}" target="_blank" rel="noopener" style="text-decoration:none;">
    <img class="m-logo m-logo-light" src="{{ $t['logo.url'] }}" alt="{{ $t['logo.alt'] ?: $productName }}" width="{{ $logoWidth }}" style="display:block;border:0;width:100%;max-width:{{ $logoWidth }}px;height:auto;margin:{{ $logoMargin }};">
    @if(($t['logo.dark_url'] ?? null))
        {{-- Swapped in by the dark-mode stylesheet. Hidden rather than absent so
             clients that ignore the media query never show two marks. --}}
        <img class="m-logo m-logo-dark" src="{{ $t['logo.dark_url'] }}" alt="{{ $t['logo.alt'] ?: $productName }}" width="{{ $logoWidth }}" style="display:none;border:0;width:100%;max-width:{{ $logoWidth }}px;height:auto;margin:{{ $logoMargin }};mso-hide:all;">
    @endif
</a>
