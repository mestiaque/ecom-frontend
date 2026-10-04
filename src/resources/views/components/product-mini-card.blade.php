@props(['product', 'meta' => null])
{{-- Compact horizontal product row: sidebar lists, search suggestions, best sellers. --}}
<a href="{{ route('efront.product', $product) }}" {{ $attributes->merge(['class' => 'ef-mini-card']) }}>
    <span class="ef-mini-card-img">
        <img src="{{ $product->thumbnail ?? asset('efront/img/banner-img.jpg') }}" alt="{{ $product->title }}" loading="lazy">
    </span>
    <span class="ef-mini-card-body">
        <span class="ef-mini-card-title">{{ \Illuminate\Support\Str::limit($product->title, 42) }}</span>
        <x-efront::rating :rating="$product->rating" size=".68rem" />
        <x-efront::price :product="$product" class="ef-mini-card-price" />
        @if($meta)
            <span class="ef-mini-card-meta">{{ $meta }}</span>
        @endif
    </span>
</a>
