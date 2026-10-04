@extends('efront::layouts.app')

@section('title', 'Shopping Cart')

@section('content')
    <x-efront::page-header title="Shopping Cart" :breadcrumbs="[['label' => 'Cart', 'url' => null]]" />

    <section class="ef-cart">
        <div class="container" id="cartContent">
            @include('efront::cart.partials.content')
        </div>
    </section>
@endsection
