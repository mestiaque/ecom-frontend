@extends('efront::account.layout')

@section('title', 'Wishlist')
@section('account_title', 'Wishlist')

@section('account')
    @if($products->isEmpty())
        <div class="fcard">
            <x-efront::empty icon="far fa-heart" title="Your wishlist is empty" text="Tap the heart on any product to save it here.">
                <a href="{{ route('efront.shop') }}" class="btn-red mt-2"><i class="fas fa-store"></i>Browse Products</a>
            </x-efront::empty>
        </div>
    @else
        <div class="row g-4">
            @foreach($products as $product)
                <div class="col-6 col-md-4" data-wishlist-item="{{ $product->id }}">
                    <x-efront::product-card :product="$product" class="h-100" />
                </div>
            @endforeach
        </div>
        <div class="ef-pagination mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
    @endif
@endsection
