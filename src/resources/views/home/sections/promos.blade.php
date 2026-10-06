@if($promos->isNotEmpty())
    @php
        // Columns by count, so the row never has an empty slot: 1 → wide, 2 → halves, 3 → thirds, 4 → 2 × 2
        $col = match ($promos->count()) {
            1 => 'col-lg-10',
            3 => 'col-md-6 col-lg-4',
            default => 'col-md-6',
        };
        $withText = config('efront.banner_text');
    @endphp
    <section class="ef-promos pb-0" data-ef-sec="promos" style="{{ efront_theme()->sectionStyle('promos') }}">
        <div class="container">
            @if(filled($section['title'] ?? null))
                <x-efront::section-title :label="$section['label']" :title="$section['title']" :highlight="$section['highlight']" :text="$section['text']" />
            @endif
            <div class="row g-4 justify-content-center">
                @foreach($promos as $promo)
                    <div class="{{ $col }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <a href="{{ $promo->link ? url($promo->link) : route('efront.shop') }}" @class(['ef-promo', 'ef-promo-plain' => ! $withText]) title="{{ $promo->title }}">
                            <img src="{{ $promo->image_url }}" alt="{{ $promo->title }}" class="ef-promo-img" loading="lazy">
                            @if($withText)
                                <span class="ef-promo-body">
                                    @if($promo->subtitle)<span class="ef-promo-sub">{{ $promo->subtitle }}</span>@endif
                                    @if($promo->title)<span class="ef-promo-title">{{ $promo->title }}</span>@endif
                                    <span class="ef-promo-btn">{{ $promo->button_text ?: 'Shop Now' }} <i class="fas fa-arrow-right"></i></span>
                                </span>
                            @else
                                {{-- The designed image already shows its text; keep it for screen readers only --}}
                                <span class="visually-hidden">{{ $promo->title }} — {{ $promo->button_text ?: 'Shop Now' }}</span>
                                <span class="ef-promo-go" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
