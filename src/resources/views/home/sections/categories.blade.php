@if($categories->isNotEmpty())
    <section id="category" data-ef-sec="categories" style="{{ efront_theme()->sectionStyle('categories') }}">
        <div class="container">
            <x-efront::section-title :label="$section['label']" :title="$section['title']" :highlight="$section['highlight']" :text="$section['text']" />
            <div class="row g-3 justify-content-center">
                @foreach($categories->take($section['limit']) as $category)
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2" data-aos="zoom-in" data-aos-delay="{{ ($loop->index % 6) * 70 }}">
                        <x-efront::category-card :category="$category" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
