{{-- Multi-section treatment for newsletters and digests: centred masthead,
     roomier rhythm, social row in the footer. --}}
@extends('mailyte::html.layouts.document')

@section('header')
    @include('mailyte::html.partials.header', ['logoAlign' => 'center'])
@endsection

@section('content')
    {!! $content !!}
@endsection

@section('footer')
    @if(!empty($t['social']))
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
            <tr>
                <td align="center" style="padding:8px 0 0;font-family:{{ $t['font.body'] }};font-size:{{ $t['type.small.size'] ?? '14px' }};line-height:22px;">
                    @foreach($t['social'] as $social)
                        <a href="{{ $social['url'] ?? '#' }}" style="color:{{ $t['color.link'] }};text-decoration:underline;padding:0 8px;">{{ $social['name'] ?? '' }}</a>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif
    @include('mailyte::html.partials.footer')
@endsection
