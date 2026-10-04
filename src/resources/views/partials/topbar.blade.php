<div id="topbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="top-contact d-flex flex-wrap">
                @if($phone = efront()->setting('store_phone'))
                    <span><i class="fas fa-phone-alt"></i><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="text-reset">{{ $phone }}</a></span>
                @endif
                @if($email = efront()->setting('store_email'))
                    <span class="d-none d-md-inline"><i class="fas fa-envelope"></i><a href="mailto:{{ $email }}" class="text-reset">{{ $email }}</a></span>
                @endif
                @if($hours = efront()->setting('store_hotline_hours'))
                    <span class="d-none d-lg-inline"><i class="fas fa-clock"></i>{{ $hours }}</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-3">
                @if(! is_null($freeMin = efront()->freeShippingMin()))
                    <span class="ttag"><i class="fas fa-truck me-1"></i>Free delivery over {{ ecom_money($freeMin) }}</span>
                @endif
                <a href="{{ efront()->trackUrl() }}" class="ef-toplink"><i class="fas fa-location-crosshairs me-1"></i>Track Order</a>
                <div class="tsoc d-none d-sm-block">
                    @foreach(efront()->socialLinks() as $icon => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"><i class="{{ $icon }}"></i></a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
