{{-- The default product treatment: logo leader, full palette, and a footer that
     always carries the social row, the postal address and a copyright line.
     Those three are the ones a brand is judged on and the ones compliance asks
     about, so this layout does not let a template quietly drop them -- a
     template that wants them gone should use `minimal` or `plain` instead. --}}
@extends('mailyte::html.layouts.document')

@section('header')
    @include('mailyte::html.partials.header')
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
