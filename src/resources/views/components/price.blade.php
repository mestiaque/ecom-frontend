@props(['product', 'variant' => null])
@php
    $final = $product->finalPrice($variant);
    $regular = $variant ? $variant->regular_price : (float) $product->price;
@endphp
<div {{ $attributes->merge(['class' => 'mprice']) }}>
    <span data-price>{{ ecom_money($final) }}</span>
    <small data-regular-price @if($regular <= $final) hidden @endif>{{ ecom_money($regular) }}</small>
</div>
