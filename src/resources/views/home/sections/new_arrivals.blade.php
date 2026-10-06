@if($newArrivals->isNotEmpty())
    <section class="ef-arrivals" data-ef-sec="new_arrivals" style="{{ efront_theme()->sectionStyle('new_arrivals') }}">
        <div class="container">
            <x-efront::section-title :label="$section['label']" :title="$section['title']" :highlight="$section['highlight']" :text="$section['text']" />
            <div class="swiper productSwiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    @foreach($newArrivals as $product)
                        <div class="swiper-slide h-auto"><x-efront::product-card :product="$product" class="h-100" /></div>
                    @endforeach
                </div>
                <div class="swiper-pagination position-static mt-4"></div>
            </div>
        </div>
    </section>
@endif
