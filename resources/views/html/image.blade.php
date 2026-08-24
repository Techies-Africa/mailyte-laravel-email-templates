<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td align="{{ $props['align'] }}" style="padding:0 0 {{ $props['space_below'] }};">
            @if($props['href'])<a href="{{ $props['href'] }}" target="_blank" rel="noopener">@endif
            <img src="{{ $props['src'] }}" alt="{{ $props['alt'] }}" width="{{ $props['width'] }}"@if($props['height'] !== '') height="{{ $props['height'] }}"@endif style="display:block;width:100%;max-width:{{ $props['width'] }}px;height:auto;border:0;outline:none;text-decoration:none;border-radius:{{ $props['radius'] }};-ms-interpolation-mode:bicubic;">
            @if($props['href'])</a>@endif
        </td>
    </tr>
</table>
