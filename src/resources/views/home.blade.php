@extends('efront::layouts.app')

@section('content')
    {{-- HERO: "slider" banners, or the Sarab hero from config('efront.hero') --}}
    @if($sliders->isNotEmpty())
        <section class="ef-hero-slider p-0">
            <div class="swiper heroSwiper">
                <div class="swiper-wrapper">
                    @foreach($sliders as $slide)
                        <div class="swiper-slide">
                            @if(config('efront.banner_text'))
                                <div class="ef-slide" style="background-image:url('{{ ecom_image($slide->image) }}')">
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
                                    <img src="{{ ecom_image($slide->image) }}" alt="{{ $slide->title }}" @unless($loop->first) loading="lazy" @endunless>
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
        @php($hero = config('efront.hero'))
        <section id="hero">
            <div class="hs hs1"></div>
            <div class="hs hs2"></div>
            <div class="hbgtxt">SHOP</div>
            <div class="container">
                <div class="row align-items-center g-5" style="min-height:80vh;">
                    <div class="col-lg-6">
                        <div class="hbadge"><div class="hbi"><i class="fas fa-star"></i></div><span>{{ $hero['badge'] }}</span></div>
                        <h1 class="htitle">{{ $hero['title'] }} <span class="hl">{{ $hero['highlight'] }}</span></h1>
                        <p class="hdesc">{{ $hero['text'] }}</p>
                        <div class="d-flex flex-wrap gap-3 mb-2">
                            <a href="{{ route('efront.shop') }}" class="btn-red"><i class="fas fa-store"></i>Shop Now</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div style="position:relative;text-align:center;">
                            <div class="hcircle"><img src="{{ asset($hero['image']) }}" alt=""></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- MARQUEE --}}
    @if($categories->isNotEmpty())
        <div class="mqsec">
            <div class="mqtrack">
                @foreach([1, 2] as $loopCopy)
                    @foreach($categories as $category)
                        <a class="mqitem" href="{{ route('efront.category', $category) }}"><i class="fas fa-circle"></i>{{ $category->name }}</a>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- FEATURES --}}
    <section class="ef-features py-4">
        <div class="container">
            <div class="row g-3">
                @foreach([
                    ['r', 'fas fa-shipping-fast', 'Fast Delivery', is_null(efront()->freeShippingMin()) ? 'Anywhere in the country' : 'Free over '.ecom_money(efront()->freeShippingMin())],
                    ['y', 'fas fa-hand-holding-usd', 'Cash on Delivery', 'Pay when you receive'],
                    ['g', 'fas fa-undo', 'Easy Returns', 'Hassle-free return policy'],
                    ['r', 'fas fa-headset', 'Support', efront()->setting('store_phone', 'We are here to help')],
                ] as [$tone, $icon, $title, $text])
                    <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ $loop->index * 70 }}">
                        <div class="fti ef-feature mb-0">
                            <div class="ftico {{ $tone }}"><i class="{{ $icon }}"></i></div>
                            <div><h6>{{ $title }}</h6><p class="mb-0">{{ $text }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CATEGORIES --}}
    @if($categories->isNotEmpty())
        <section id="category">
            <div class="container">
                <x-efront::section-title label="What We Offer" title="Browse by" highlight="Category" text="Find exactly what you need from our hand-picked collections." />
                <div class="row g-3 justify-content-center">
                    @foreach($categories->take(12) as $category)
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2" data-aos="zoom-in" data-aos-delay="{{ ($loop->index % 6) * 70 }}">
                            <x-efront::category-card :category="$category" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FEATURED PRODUCTS (filter by category, like the Sarab menu) --}}
    @if($featured->isNotEmpty())
        <section id="menu">
            <div class="container">
                <x-efront::section-title label="Hand Picked" title="Featured" highlight="Products" />
                @if($featuredCategories->count() > 1)
                    <div class="text-center mb-4" data-aos="fade-up">
                        <button type="button" class="filtbtn active" data-f="all">All</button>
                        @foreach($featuredCategories as $category)
                            <button type="button" class="filtbtn" data-f="{{ $category->id }}">{{ $category->name }}</button>
                        @endforeach
                    </div>
                @endif
                <div class="row g-4" id="mgrid">
                    @foreach($featured as $product)
                        <div class="col-6 col-lg-4 mwrap" data-c="{{ $product->category_id }}" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 80 }}">
                            <x-efront::product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-5"><a href="{{ route('efront.shop') }}" class="btn-red"><i class="fas fa-th-large"></i>View All Products</a></div>
            </div>
        </section>
    @endif

    {{-- RUNNING CAMPAIGN (Sarab special offer + countdown) --}}
    @if($campaign)
        <section id="special">
            <div class="spbg"></div>
            <div class="container" style="position:relative;z-index:2;">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6" data-aos="fade-right">
                        <div class="sptag"><i class="fas fa-bolt me-1"></i>Limited Time Offer</div>
                        <h2 class="sptitle">{{ $campaign->title }}<br><span>{{ $campaign->discount_type === 'percent' ? (float) $campaign->discount_value.'% Off' : ecom_money($campaign->discount_value).' Off' }}</span></h2>
                        @if($campaign->description)
                            <p class="spdesc">{{ \Illuminate\Support\Str::limit(strip_tags($campaign->description), 180) }}</p>
                        @endif
                        <div class="cdwrap" data-countdown="{{ $campaign->ends_at->toIso8601String() }}">
                            <div class="cditem"><span class="cdnum" data-cd="d">00</span><span class="cdlbl">Days</span></div>
                            <div class="cditem"><span class="cdnum" data-cd="h">00</span><span class="cdlbl">Hours</span></div>
                            <div class="cditem"><span class="cdnum" data-cd="m">00</span><span class="cdlbl">Minutes</span></div>
                            <div class="cditem"><span class="cdnum" data-cd="s">00</span><span class="cdlbl">Seconds</span></div>
                        </div>
                        <a href="{{ route('efront.campaign', $campaign) }}" class="btn-red"><i class="fas fa-shopping-cart"></i>Grab the Deal</a>
                    </div>
                    <div class="col-lg-6" data-aos="fade-left">
                        @if($campaign->banner)
                            <div class="spimgw">
                                <div class="spglow"></div>
                                <img src="{{ ecom_image($campaign->banner) }}" alt="{{ $campaign->title }}">
                            </div>
                        @else
                            <div class="ef-campaign-products">
                                @foreach($campaign->products as $product)
                                    <x-efront::product-mini-card :product="$product" class="ef-mini-card-dark" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- PROMO BANNERS --}}
    @if($promos->isNotEmpty())
        <section class="ef-promos pb-0">
            <div class="container">
                <div class="row g-4">
                    @foreach($promos as $promo)
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 2) * 80 }}">
                            <a href="{{ $promo->link ? url($promo->link) : route('efront.shop') }}" @class(['ef-promo', 'ef-promo-plain' => ! config('efront.banner_text')]) title="{{ $promo->title }}" style="background-image:url('{{ ecom_image($promo->image) }}')">
                                <span class="ef-promo-body">
                                    @if($promo->subtitle)<span class="ef-promo-sub">{{ $promo->subtitle }}</span>@endif
                                    @if($promo->title)<span class="ef-promo-title">{{ $promo->title }}</span>@endif
                                    <span class="ef-promo-btn">{{ $promo->button_text ?: 'Shop Now' }} <i class="fas fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- NEW ARRIVALS --}}
    @if($newArrivals->isNotEmpty())
        <section class="ef-arrivals">
            <div class="container">
                <x-efront::section-title label="Just In" title="New" highlight="Arrivals" />
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

    {{-- MINI LISTS --}}
    <section class="ef-lists pt-0">
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

    {{-- BRANDS --}}
    @if($brands->isNotEmpty())
        <section class="ef-brands py-5">
            <div class="container">
                <div class="swiper brandSwiper">
                    <div class="swiper-wrapper align-items-center">
                        @foreach($brands as $brand)
                            <div class="swiper-slide">
                                <a href="{{ route('efront.brand', $brand) }}" class="ef-brand" title="{{ $brand->name }}">
                                    <img src="{{ ecom_image($brand->logo) }}" alt="{{ $brand->name }}" loading="lazy">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- TESTIMONIALS (approved 4-5 star reviews) --}}
    @if($testimonials->isNotEmpty())
        <section id="testimonials">
            <div class="container">
                <x-efront::section-title label="What People Say" title="Our Customers" highlight="Feedback" />
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

    {{-- ACCOUNT CTA (Sarab newsletter band) --}}
    @unless(efront()->customer())
        <section id="newsletter">
            <div class="nlbg"></div>
            <div class="container">
                <div class="nlw text-center" data-aos="zoom-in">
                    <span class="slbl" style="color:rgba(255,255,255,.7);">Join Us</span>
                    <h2 class="mb-3" style="color:#fff;">Create an account &amp; shop <span style="color:var(--secondary);">faster</span></h2>
                    <p class="mb-4" style="color:rgba(255,255,255,.78);">Save your address, follow your orders and keep a wishlist of things you love.</p>
                    <div class="d-flex justify-content-center flex-wrap gap-3">
                        <a href="{{ route('efront.account.register') }}" class="nlbtn"><i class="fas fa-user-plus me-1"></i>Create Account</a>
                        <a href="{{ efront()->trackUrl() }}" class="nlbtn ef-nlbtn-ghost"><i class="fas fa-location-crosshairs me-1"></i>Track an Order</a>
                    </div>
                </div>
            </div>
        </section>
    @endunless
@endsection
