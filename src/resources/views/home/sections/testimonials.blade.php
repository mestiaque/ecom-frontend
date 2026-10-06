{{-- Approved 4-5 star reviews --}}
@if($testimonials->isNotEmpty())
    <section id="testimonials" data-ef-sec="testimonials" style="{{ efront_theme()->sectionStyle('testimonials') }}">
        <div class="container">
            <x-efront::section-title :label="$section['label']" :title="$section['title']" :highlight="$section['highlight']" :text="$section['text']" />
            <div class="swiper tesSwiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    @foreach($testimonials as $review)
                        <div class="swiper-slide">
                            <div class="tescard">
                                <div class="tesq">"</div>
                                <x-efront::rating :rating="$review->rating" class="tess" />
                                <p class="testxt">{{ \Illuminate\Support\Str::limit($review->comment, 220) }}</p>
                                <div class="tesauth">
                                    <span class="ef-avatar">{{ mb_strtoupper(mb_substr($review->name, 0, 1)) }}</span>
                                    <div>
                                        <div class="tesnm">{{ $review->name }}</div>
                                        @if($review->product)
                                            <a href="{{ route('efront.product', $review->product) }}" class="tesrl">{{ \Illuminate\Support\Str::limit($review->product->title, 34) }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination mt-4" style="position:static;"></div>
            </div>
        </div>
    </section>
@endif
