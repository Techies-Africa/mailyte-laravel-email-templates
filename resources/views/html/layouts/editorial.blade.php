{{-- Multi-section treatment for newsletters and digests: centred masthead,
     roomier rhythm, and the social row given real presence rather than a line
     of underlined words. --}}
@extends('mailyte::html.layouts.document')

@section('header')
    @include('mailyte::html.partials.header', ['logoAlign' => $t['header.align'] ?? 'center'])
@endsection

@section('content')
    {!! $content !!}
@endsection

@section('footer')
    @include('mailyte::html.partials.footer', [
        'footerAlign' => 'center',
        'showSocial' => true,
        'showAddress' => true,
        'showCopyright' => true,
    ])
@endsection
