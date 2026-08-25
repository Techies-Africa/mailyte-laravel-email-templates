{{-- Text-forward with a small wordmark. No hero, no imagery, tighter footer.
     The Linear / Postmark-plain register. --}}
@extends('mailyte::html.layouts.document')

@section('header')
    @if(($globals['product']['name'] ?? '') !== '')
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
            <tr>
                <td style="padding:4px 0 20px;font-family:{{ $t['font.heading'] }};font-size:15px;line-height:20px;font-weight:600;letter-spacing:0.02em;color:{{ $t['color.text_muted'] }};">
                    {{ $globals['product']['name'] }}
                </td>
            </tr>
        </table>
    @endif
@endsection

@section('content')
    {!! $content !!}
@endsection

@section('footer')
    @include('mailyte::html.partials.footer', [
        'showSocial' => true,
        'showAddress' => true,
        'showCopyright' => true,
    ])
@endsection
