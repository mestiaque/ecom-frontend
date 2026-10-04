@props(['product'])
@php($active = efront()->inWishlist($product->id))
<button type="button" {{ $attributes->merge(['class' => 'mhrt'.($active ? ' active' : '')]) }} data-wishlist="{{ $product->id }}" title="{{ $active ? 'Remove from wishlist' : 'Add to wishlist' }}" aria-pressed="{{ $active ? 'true' : 'false' }}">
    <i class="{{ $active ? 'fas' : 'far' }} fa-heart"></i>
</button>
