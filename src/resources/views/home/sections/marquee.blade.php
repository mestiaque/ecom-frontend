@if($categories->isNotEmpty())
    @php
        // Two identical halves, each wider than any screen; the track moves exactly one half, so the loop has no visible start or end
        $perHalf = (int) ceil(18 / $categories->count());
        $items = collect(range(1, $perHalf))->flatMap(fn () => $categories);
    @endphp
    <div class="mqsec" data-ef-sec="marquee" style="{{ efront_theme()->sectionStyle('marquee') }}">
        <div class="mqtrack" style="--mq-duration: {{ $items->count() * 2.6 }}s">
            @foreach([false, true] as $isCopy)
                <div class="mqgroup" @if($isCopy) aria-hidden="true" @endif>
                    @foreach($items as $category)
                        <a class="mqitem" href="{{ route('efront.category', $category) }}" @if($isCopy) tabindex="-1" @endif><i class="fas fa-circle"></i>{{ $category->name }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif
