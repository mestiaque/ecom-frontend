{{-- FEATURED PRODUCTS (filter by category, like the Sarab menu) --}}
@if($featured->isNotEmpty())
    <section id="menu" data-ef-sec="featured" style="{{ efront_theme()->sectionStyle('featured') }}">
        <div class="container">
            <x-efront::section-title :label="$section['label']" :title="$section['title']" :highlight="$section['highlight']" :text="$section['text']" />
            @if($section['filter'] && $featuredCategories->count() > 1)
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
                        <x-efront::product-card :product="$product" class="h-100" />
                    </div>
                @endforeach
            </div>
            @if($section['button'])
                <div class="text-center mt-5"><a href="{{ route('efront.shop') }}" class="btn-red"><i class="fas fa-th-large"></i>{{ $section['button'] }}</a></div>
            @endif
        </div>
    </section>
@endif
