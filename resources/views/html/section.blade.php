{{-- The band carries its own background on the <td> and repeats it as a bgcolor
     attribute, because Outlook honours the attribute where it sometimes drops
     the style. Inline colour on the cell also survives Gmail's dark-mode
     inversion better than a class would. --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    {{-- Every tone gets a hook. `m-alt`/`m-canvas` are repainted by the
                         dark stylesheet; `m-hold` is deliberately absent from it, so a
                         band that is already dark keeps the colour it was given. --}}
                    <td @class([
                        'm-alt' => in_array($props['tone'], ['alt', 'custom'], true),
                        'm-canvas' => $props['tone'] === 'surface',
                        'm-hold' => in_array($props['tone'], ['dark', 'accent'], true),
                    ])
                        bgcolor="{{ $props['background'] }}"
                        align="{{ $props['align'] }}"
                        style="background-color:{{ $props['background'] }};color:{{ $props['ink'] }};padding:{{ $props['padding_y'] }} {{ $props['padding_x'] }};@if($props['radius'] !== '0')border-radius:{{ $props['radius'] }};@endif @if($props['border_color'])border:1px solid {{ $props['border_color'] }};@endif">
                        {!! $props['slot'] !!}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
