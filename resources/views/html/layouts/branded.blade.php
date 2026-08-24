{{-- The default SaaS treatment: logo header, full palette, standard footer. --}}
@extends('mailyte::html.layouts.document')

@section('header')
    @include('mailyte::html.partials.header', ['logoAlign' => 'left'])
@endsection

@section('content')
    {!! $content !!}
@endsection

@section('footer')
    @include('mailyte::html.partials.footer')
@endsection
