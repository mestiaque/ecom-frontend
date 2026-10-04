@if($products->isEmpty())
    <div class="ef-suggest-empty">No products found for "{{ $term }}".</div>
@else
    <div class="ef-suggest-grid">
        @foreach($products as $product)
            <x-efront::product-mini-card :product="$product" />
        @endforeach
    </div>
    @if($count > $products->count())
        <a href="{{ route('efront.shop', ['q' => $term]) }}" class="ef-suggest-all">See all {{ $count }} results <i class="fas fa-arrow-right"></i></a>
    @endif
@endif
