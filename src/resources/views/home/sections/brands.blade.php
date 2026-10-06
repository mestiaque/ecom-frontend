@if($brands->isNotEmpty())
    <section class="ef-brands py-5" data-ef-sec="brands" style="{{ efront_theme()->sectionStyle('brands') }}">
        <div class="container">
            <div class="swiper brandSwiper">
                <div class="swiper-wrapper align-items-center">
                    @foreach($brands as $brand)
                        <div class="swiper-slide">
                            <a href="{{ route('efront.brand', $brand) }}" class="ef-brand" title="{{ $brand->name }}">
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" loading="lazy">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
