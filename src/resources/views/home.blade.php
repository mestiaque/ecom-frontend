@extends('efront::layouts.app')

{{-- Sections, their order and texts come from Admin → Storefront Theme → Home Page (resources/views/home/sections/*) --}}
@section('content')
    @foreach($sections as $key => $section)
        @include('efront::home.sections.'.$key, ['section' => $section])
    @endforeach
@endsection
