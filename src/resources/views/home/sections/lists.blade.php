@if($bestSellers->isNotEmpty() || $topRated->isNotEmpty() || $onSale->isNotEmpty())
    <section class="ef-lists pt-0" data-ef-sec="lists" style="{{ efront_theme()->sectionStyle('lists') }}">
        <div class="container">
            <div class="row g-4">
                @foreach([['Best', 'Sellers', $bestSellers], ['Top', 'Rated', $topRated], ['On', 'Sale', $onSale]] as [$first, $second, $items])
                    @if($items->isNotEmpty())
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                            <h4 class="ef-list-title">{{ $first }} <span>{{ $second }}</span></h4>
                            <div class="d-grid gap-3">
                                @foreach($items as $product)
                                    <x-efront::product-mini-card :product="$product" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
