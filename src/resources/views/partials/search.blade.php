<div id="searchOv">
    <button class="sovclose" type="button" data-search-close aria-label="Close"><i class="fas fa-times"></i></button>
    <div class="sovbox">
        <h4>What are you looking for?</h4>
        <form class="sovinput" action="{{ route('efront.shop') }}" method="GET" role="search">
            <input type="text" name="q" id="searchInput" placeholder="Search products, brands..." autocomplete="off" value="{{ request('q') }}">
            <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
        </form>
        <div id="searchResults" class="ef-suggest"></div>
        @if(efront()->menuCategories()->isNotEmpty())
            <div class="sovcats">
                @foreach(efront()->menuCategories()->take(7) as $searchCategory)
                    <a class="sovcat" href="{{ route('efront.category', $searchCategory) }}">
                        @if($searchCategory->image)
                            <img src="{{ ecom_image($searchCategory->image) }}" alt="">
                        @endif
                        {{ $searchCategory->name }}
                    </a>
                @endforeach
            </div>
        @endif
        <div class="sovtrend">
            <p><i class="fas fa-fire me-1" style="color:var(--secondary);"></i>Popular</p>
            <a class="ttag" href="{{ route('efront.shop', ['sort' => 'popular']) }}">Best sellers</a>
            <a class="ttag" href="{{ route('efront.shop', ['sort' => 'rating']) }}">Top rated</a>
            <a class="ttag" href="{{ route('efront.shop') }}">New arrivals</a>
            <a class="ttag" href="{{ route('efront.shop', ['in_stock' => 1, 'sort' => 'price_asc']) }}">Lowest price</a>
        </div>
    </div>
</div>
