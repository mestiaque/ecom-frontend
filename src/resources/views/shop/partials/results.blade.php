<div class="ef-toolbar">
    <button class="ef-btn-outline d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#shopFilterPanel"><i class="fas fa-sliders-h me-1"></i>Filters</button>
    <div class="ef-toolbar-count">
        @if($products->total())
            Showing <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong> of <strong>{{ $products->total() }}</strong> products
        @else
            No products found
        @endif
    </div>
    <label class="ef-sort">
        <span class="d-none d-sm-inline">Sort by</span>
        <select class="fctrl" data-sort aria-label="Sort products">
            @foreach($sorts as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
</div>

@if($products->isEmpty())
    <x-efront::empty icon="fas fa-search" title="No products match your filters" text="Try removing a filter or searching for something else.">
        <a href="{{ route('efront.shop') }}" class="btn-red mt-2"><i class="fas fa-store"></i>View All Products</a>
    </x-efront::empty>
@else
    <div class="row g-4">
        @foreach($products as $product)
            <div class="col-6 col-md-4">
                <x-efront::product-card :product="$product" class="h-100" />
            </div>
        @endforeach
    </div>
    <div class="ef-pagination mt-5">
        {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endif
