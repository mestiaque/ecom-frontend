{{-- HERO: "slider" banners (Website → Banners), or the text hero from Storefront Theme → Home Page --}}
@if($sliders->isNotEmpty())
    <section class="ef-hero-slider p-0" data-ef-sec="hero" style="{{ efront_theme()->sectionStyle('hero') }}">
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                @foreach($sliders as $slide)
                    <div class="swiper-slide">
                        @if(config('efront.banner_text'))
                            <div class="ef-slide" style="background-image:url('{{ $slide->image_url }}')">
                                <div class="container">
                                    <div class="ef-slide-content">
                                        @if($slide->subtitle)
                                            <div class="hbadge"><div class="hbi"><i class="fas fa-star"></i></div><span>{{ $slide->subtitle }}</span></div>
                                        @endif
                                        @if($slide->title)
                                            <h1 class="htitle">{{ $slide->title }}</h1>
                                        @endif
                                        @if($slide->link)
                                            <a href="{{ url($slide->link) }}" class="btn-red"><i class="fas fa-shopping-bag"></i>{{ $slide->button_text ?: 'Shop Now' }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ $slide->link ? url($slide->link) : route('efront.shop') }}" class="ef-slide-img">
                                <img src="{{ $slide->image_url }}" alt="{{ $slide->title }}" @unless($loop->first) loading="lazy" @endunless>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </section>
@else
    <section id="hero" data-ef-sec="hero" style="{{ efront_theme()->sectionStyle('hero') }}">
        <div class="hs hs1"></div>
        <div class="hs hs2"></div>
        <div class="hbgtxt">SHOP</div>
        <div class="container">
            <div class="row align-items-center g-5" style="min-height:80vh;">
                <div class="col-lg-6">
                    @if($section['badge'])
                        <div class="hbadge"><div class="hbi"><i class="fas fa-star"></i></div><span>{{ $section['badge'] }}</span></div>
                    @endif
                    <h1 class="htitle">{{ $section['title'] }} @if($section['highlight'])<span class="hl">{{ $section['highlight'] }}</span>@endif</h1>
                    @if($section['text'])<p class="hdesc">{{ $section['text'] }}</p>@endif
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <a href="{{ route('efront.shop') }}" class="btn-red"><i class="fas fa-store"></i>{{ $section['button'] ?: 'Shop Now' }}</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div style="position:relative;text-align:center;">
                        <div class="hcircle"><img src="{{ asset(config('efront.hero.image')) }}" alt=""></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
