{{-- Ghost-table construction mirrors columns.blade.php: Outlook gets fixed
     pixel widths, everything else gets percentage cells that stack under 600px
     via .m-stack. When reversed the text cell comes first in source order, so
     on mobile the words lead and the picture follows -- consistent within a
     row, which is what matters. --}}
@php($imgCell = $props['image_percent'])
@php($txtCell = $props['text_percent'])
@php($first = $props['reverse'] ? $props['text_cell'] : $props['image_cell'])
@php($second = $props['reverse'] ? $props['image_cell'] : $props['text_cell'])

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
    <tr>
        <td style="padding:0 0 {{ $props['space_below'] }};">
            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
                <td valign="top" width="{{ $first }}">
                <td valign="top" width="{{ $second }}">
            <![endif]-->
            <!--[if !mso]><!-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;"><tr>
            <!--<![endif]-->


            @if(! $props['image'])
                {{-- No image: one full-width cell, not a text column beside a hole. --}}
                <td valign="top" style="padding:0;">
                    @include('mailyte::html.partials.media-text', ['props' => $props, 't' => $t])
                </td>
            @elseif($props['reverse'])
                <td class="m-stack" valign="top" width="{{ $txtCell }}%" style="width:{{ $txtCell }}%;padding:0 16px {{ $props['space_below'] }} 0;">
                    @include('mailyte::html.partials.media-text', ['props' => $props, 't' => $t])
                </td>
                <td class="m-stack" valign="top" width="{{ $imgCell }}%" style="width:{{ $imgCell }}%;padding:0;">
                    @include('mailyte::html.partials.media-image', ['props' => $props])
                </td>
            @else
                <td class="m-stack" valign="top" width="{{ $imgCell }}%" style="width:{{ $imgCell }}%;padding:0 16px {{ $props['space_below'] }} 0;">
                    @include('mailyte::html.partials.media-image', ['props' => $props])
                </td>
                <td class="m-stack" valign="top" width="{{ $txtCell }}%" style="width:{{ $txtCell }}%;padding:0;">
                    @include('mailyte::html.partials.media-text', ['props' => $props, 't' => $t])
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
