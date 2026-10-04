<div class="offcanvas-lg offcanvas-start ef-filters" tabindex="-1" id="shopFilterPanel" aria-labelledby="shopFilterLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="shopFilterLabel">Filters</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#shopFilterPanel" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-block">
        <form id="shopFilters" action="{{ url()->current() }}" method="GET" data-ajax-filter>
            @if(request('q'))
                <input type="hidden" name="q" value="{{ request('q') }}">
            @endif
            @if(request('category') && ! isset($brand) && ! isset($campaign) && request()->routeIs('efront.shop'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <input type="hidden" name="sort" value="{{ $sort }}">

            <div class="ef-filter-box">
                <h6 class="ef-filter-title">Categories</h6>
                <ul class="ef-cat-list">
                    <li><a href="{{ route('efront.shop', array_filter(['q' => request('q')])) }}" @class(['active' => ! ($category ?? null)])>All Products</a></li>
                    @foreach($categories as $menuCategory)
                        @php($open = ($category ?? null) && ($category->id === $menuCategory->id || $category->parent_id === $menuCategory->id))
                        <li>
                            <a href="{{ route('efront.category', $menuCategory) }}" @class(['active' => ($category ?? null)?->id === $menuCategory->id])>
                                {{ $menuCategory->name }} <span>{{ $menuCategory->products_count }}</span>
                            </a>
                            @if($open && $menuCategory->children->isNotEmpty())
                                <ul>
                                    @foreach($menuCategory->children as $child)
                                        <li><a href="{{ route('efront.category', $child) }}" @class(['active' => $category->id === $child->id])>{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="ef-filter-box">
                <h6 class="ef-filter-title">Price ({{ config('ecom.currency_symbol') }})</h6>
                <div class="d-flex gap-2 align-items-center">
                    <input type="number" name="min_price" class="fctrl" placeholder="{{ $priceMin }}" value="{{ request('min_price') }}" min="0" aria-label="Minimum price">
                    <span class="text-muted">–</span>
                    <input type="number" name="max_price" class="fctrl" placeholder="{{ $priceMax }}" value="{{ request('max_price') }}" min="0" aria-label="Maximum price">
                </div>
                <button type="submit" class="ef-btn-outline w-100 justify-content-center mt-2">Apply</button>
            </div>

            @if($brands->isNotEmpty() && ! isset($brand))
                <div class="ef-filter-box">
                    <h6 class="ef-filter-title">Brands</h6>
                    <div class="ef-check-list">
                        @foreach($brands as $filterBrand)
                            <label class="ef-check">
                                <input type="checkbox" name="brands[]" value="{{ $filterBrand->id }}" @checked(in_array($filterBrand->id, (array) request('brands')))>
                                <span>{{ $filterBrand->name }}</span>
                                <small>{{ $filterBrand->products_count }}</small>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="ef-filter-box">
                <h6 class="ef-filter-title">Availability</h6>
                <label class="ef-check">
                    <input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock'))>
                    <span>In stock only</span>
                </label>
            </div>

            <a href="{{ url()->current() }}{{ request('q') ? '?q='.urlencode(request('q')) : '' }}" class="ef-clear-filters"><i class="fas fa-rotate-left me-1"></i>Clear all filters</a>
        </form>
    </div>
</div>
