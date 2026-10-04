{{-- Loaded into #qvBody (layout) by efront.js --}}
<div class="mpimg">
    <img src="{{ $product->images->first()?->url ?? asset('efront/img/banner-img.jpg') }}" alt="{{ $product->title }}" data-gallery-image>
</div>
<div class="mpbody">
    @if($product->category)
        <div id="mpCat">{{ $product->category->name }}</div>
    @endif
    <div id="mpTitle">{{ $product->title }}</div>
    <div id="mpStars"><x-efront::rating :rating="$product->rating" :count="$product->reviews_count" /></div>
    @if($product->short_description)
        <div id="mpDesc">{{ \Illuminate\Support\Str::limit($product->short_description, 220) }}</div>
    @endif
    @include('efront::product.partials.purchase')
    <a href="{{ route('efront.product', $product) }}" class="ef-link-more">View full details <i class="fas fa-arrow-right"></i></a>
</div>
