@php($isRule = in_array($props['style'], ['dotted', 'dashed', 'double'], true))
@php($height = $props['style'] === 'thick' ? 3 : 1)
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td align="{{ $props['align'] }}" style="padding:{{ $props['space_above'] }} 0 {{ $props['space_below'] }};">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="{{ $props['width'] }}" style="border-collapse:collapse;width:{{ $props['width'] }};">
                <tr>
                    @if($isRule)
                        {{-- A dotted or dashed rule has to be a border, not a background:
                             Outlook renders border-style faithfully and would show a solid
                             band for a background-image dash pattern. --}}
                        <td class="m-divider" style="font-size:0;line-height:0;border-top:{{ $props['style'] === 'double' ? '3px double' : '1px '.$props['style'] }} {{ $props['color'] }};">&nbsp;</td>
                    @else
                        <td class="m-divider" height="{{ $height }}" style="height:{{ $height }}px;line-height:{{ $height }}px;font-size:0;background-color:{{ $props['color'] }};">&nbsp;</td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
</table>
