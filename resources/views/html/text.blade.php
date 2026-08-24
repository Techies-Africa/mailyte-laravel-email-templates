<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
        <td class="@if($props['muted'])m-muted @endif@if($props['size'] === 'body')m-body-copy@endif" align="{{ $props['align'] }}" style="padding:0 0 {{ $props['space_below'] }};">
            <p style="margin:0;font-family:{{ $t['font.body'] }};font-size:{{ $props['type']['size'] ?? '16px' }};line-height:{{ $props['type']['line_height'] ?? '26px' }};font-weight:{{ $props['type']['weight'] ?? '400' }};color:{{ $props['color'] }};text-align:{{ $props['align'] }};mso-line-height-rule:exactly;">{!! nl2br(e($props['text'])) !!}</p>
        </td>
    </tr>
</table>
