@if(($t['logo.url'] ?? null))
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
        <tr>
            <td align="{{ $logoAlign ?? 'left' }}" style="padding:8px 0 16px;">
                <a href="{{ $globals['product']['url'] ?? '#' }}" target="_blank" rel="noopener">
                    {{-- A transparent logo with dark artwork disappears on an
                         inverted background, so themes should ship a mark with
                         a baked-in stroke or plate rather than relying on a
                         prefers-color-scheme swap that only Apple Mail honours. --}}
                    <img src="{{ $t['logo.url'] }}" alt="{{ $t['logo.alt'] ?: ($globals['product']['name'] ?? '') }}" width="{{ $t['logo.width'] ?? '140' }}" style="display:block;border:0;max-width:{{ $t['logo.width'] ?? '140' }}px;height:auto;">
                </a>
            </td>
        </tr>
    </table>
@elseif(($globals['product']['name'] ?? '') !== '')
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
        <tr>
            <td align="{{ $logoAlign ?? 'left' }}" style="padding:8px 0 16px;font-family:{{ $t['font.heading'] }};font-size:18px;line-height:24px;font-weight:700;color:{{ $t['color.text'] }};">
                {{ $globals['product']['name'] }}
            </td>
        </tr>
    </table>
@endif
