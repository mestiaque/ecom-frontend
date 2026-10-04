<footer>
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                @php($footerName = efront()->storeName())
                @php($half = (int) ceil(mb_strlen($footerName) / 2))
                <div class="fnm">{{ mb_substr($footerName, 0, $half) }}<span>{{ mb_substr($footerName, $half) }}</span></div>
                <p class="fdesc">{{ efront()->setting('store_tagline', 'Genuine products, fast delivery and easy returns.') }}</p>
                <div class="fsoc">
                    @foreach(efront()->socialLinks() as $icon => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"><i class="{{ $icon }}"></i></a>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="ftit">Shop</div>
                <ul class="flinks ps-0">
                    <li><a href="{{ route('efront.home') }}"><i class="fas fa-chevron-right"></i>Home</a></li>
                    <li><a href="{{ route('efront.shop') }}"><i class="fas fa-chevron-right"></i>All Products</a></li>
                    @foreach(efront()->menuCategories()->take(4) as $footerCategory)
                        <li><a href="{{ route('efront.category', $footerCategory) }}"><i class="fas fa-chevron-right"></i>{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="ftit">Help</div>
                <ul class="flinks ps-0">
                    <li><a href="{{ route('efront.account.dashboard') }}"><i class="fas fa-chevron-right"></i>My Account</a></li>
                    <li><a href="{{ efront()->trackUrl() }}"><i class="fas fa-chevron-right"></i>Track Order</a></li>
                    <li><a href="{{ route('efront.faq') }}"><i class="fas fa-chevron-right"></i>FAQ</a></li>
                    <li><a href="{{ route('efront.contact') }}"><i class="fas fa-chevron-right"></i>Contact Us</a></li>
                    @foreach(efront()->footerPages()->whereNotIn('slug', ['contact-us', 'faq'])->take(5) as $footerPage)
                        <li><a href="{{ route('efront.page', $footerPage) }}"><i class="fas fa-chevron-right"></i>{{ $footerPage->title }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-4">
                <div class="ftit">Get In Touch</div>
                @foreach([
                    ['fas fa-map-marker-alt', 'Address', efront()->setting('store_address')],
                    ['fas fa-phone-alt', 'Phone', efront()->setting('store_phone')],
                    ['fas fa-envelope', 'Email', efront()->setting('store_email')],
                    ['fas fa-clock', 'Hours', efront()->setting('store_hotline_hours')],
                ] as [$icon, $label, $value])
                    @if($value)
                        <div class="fci">
                            <div class="fciico"><i class="{{ $icon }}"></i></div>
                            <div class="fciinfo"><strong>{{ $label }}</strong>{{ $value }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <div class="fbot">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <p>&copy; {{ date('Y') }} <span>{{ efront()->storeName() }}</span>. All rights reserved.</p>
                <div>
                    @foreach(efront()->footerPages()->whereIn('slug', ['privacy-policy', 'terms-and-conditions', 'return-policy']) as $legalPage)
                        <a href="{{ route('efront.page', $legalPage) }}">{{ $legalPage->title }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</footer>
