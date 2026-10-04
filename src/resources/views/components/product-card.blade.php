@props(['product'])
{{-- Product grid card (Sarab menu card). Needs efront()->products() eager loads: primaryImage, category, campaigns, rating, reviews_count. --}}
<div {{ $attributes->merge(['class' => 'mcard ef-card']) }}>
    <div class="mimg">
        <a href="{{ route('efront.product', $product) }}">
            <img src="{{ $product->thumbnail ?? asset('efront/img/banner-img.jpg') }}" alt="{{ $product->title }}" loading="lazy">
        </a>
        <x-efront::product-badge :product="$product" />
        <x-efront::wishlist-button :product="$product" />
    </div>
    <div class="mbody">
        <div class="d-flex align-items-center justify-content-between gap-2">
            @if($product->category)
                <a href="{{ route('efront.category', $product->category) }}" class="mcat d-inline-block text-truncate">{{ $product->category->name }}</a>
            @endif
            @if($product->free_delivery)
                <span class="ef-card-ship" title="Free delivery"><i class="fas fa-truck"></i> Free</span>
            @endif
        </div>
        <a href="{{ route('efront.product', $product) }}" class="mtit d-block">{{ \Illuminate\Support\Str::limit($product->title, 48) }}</a>
        @if($product->short_description)
            <div class="mdesc">{{ \Illuminate\Support\Str::limit(strip_tags($product->short_description), 70) }}</div>
        @endif
        <div class="mfoot">
            <div>
                <x-efront::price :product="$product" />
                <x-efront::rating :rating="$product->rating" :count="$product->reviews_count ?? 0" />
            </div>
            <button type="button" class="madd" title="Quick view" data-quick-view="{{ route('efront.product.quick-view', $product) }}" @disabled($product->stock <= 0)>
                <i class="fas {{ $product->stock > 0 ? 'fa-plus' : 'fa-ban' }}"></i>
            </button>
        </div>
    </div>
</div>
