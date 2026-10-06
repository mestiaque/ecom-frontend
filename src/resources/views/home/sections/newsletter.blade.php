{{-- Account call-to-action band (hidden for logged-in customers) --}}
@unless(efront()->customer())
    <section id="newsletter" data-ef-sec="newsletter" style="{{ efront_theme()->sectionStyle('newsletter') }}">
        <div class="nlbg"></div>
        <div class="container">
            <div class="nlw text-center" data-aos="zoom-in">
                @if($section['label'])<span class="slbl" style="color:rgba(255,255,255,.7);">{{ $section['label'] }}</span>@endif
                <h2 class="mb-3" style="color:var(--ef-sec-heading,#fff);">{{ $section['title'] }} @if($section['highlight'])<span style="color:var(--secondary);">{{ $section['highlight'] }}</span>@endif</h2>
                @if($section['text'])<p class="mb-4" style="color:rgba(255,255,255,.78);">{{ $section['text'] }}</p>@endif
                <div class="d-flex justify-content-center flex-wrap gap-3">
                    <a href="{{ route('efront.account.register') }}" class="nlbtn"><i class="fas fa-user-plus me-1"></i>Create Account</a>
                    <a href="{{ efront()->trackUrl() }}" class="nlbtn ef-nlbtn-ghost"><i class="fas fa-location-crosshairs me-1"></i>Track an Order</a>
                </div>
            </div>
        </div>
    </section>
@endunless
