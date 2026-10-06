{{-- RUNNING CAMPAIGN (special offer + countdown) --}}
@if($campaign)
    <section id="special" data-ef-sec="campaign" style="{{ efront_theme()->sectionStyle('campaign') }}">
        <div class="spbg"></div>
        <div class="container" style="position:relative;z-index:2;">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    @if($section['label'])<div class="sptag"><i class="fas fa-bolt me-1"></i>{{ $section['label'] }}</div>@endif
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
                    <a href="{{ route('efront.campaign', $campaign) }}" class="btn-red"><i class="fas fa-shopping-cart"></i>{{ $section['button'] ?: 'Shop the Deal' }}</a>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    @if($campaign->banner_url)
                        <div class="spimgw">
                            <div class="spglow"></div>
                            <img src="{{ $campaign->banner_url }}" alt="{{ $campaign->title }}">
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
