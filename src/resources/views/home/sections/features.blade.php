@php
    // Empty text falls back to live store info (free delivery limit, phone)
    $fallbacks = [
        is_null(efront()->freeShippingMin()) ? 'Anywhere in the country' : 'Free over '.ecom_money(efront()->freeShippingMin()),
        'Pay when you receive',
        'Hassle-free return policy',
        efront()->setting('store_phone', 'We are here to help'),
    ];
    $tones = ['r', 'y', 'g', 'r'];
    $items = collect($section['items'] ?? [])->filter(fn ($item) => filled($item['title'] ?? null))->values();
@endphp
@if($items->isNotEmpty())
    <section class="ef-features py-4" data-ef-sec="features" style="{{ efront_theme()->sectionStyle('features') }}">
        <div class="container">
            <div class="row g-3">
                @foreach($items as $i => $item)
                    <div class="col-6 col-lg-{{ 12 / max(1, min(4, $items->count())) }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 70 }}">
                        <div class="fti ef-feature mb-0">
                            <div class="ftico {{ $tones[$i % 4] }}"><i class="{{ \ME\Efront\Support\Theme::validIcon($item['icon'] ?? null) ? $item['icon'] : 'fas fa-check' }}"></i></div>
                            <div><h6>{{ $item['title'] }}</h6><p class="mb-0">{{ filled($item['text'] ?? null) ? $item['text'] : ($fallbacks[$i] ?? '') }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
