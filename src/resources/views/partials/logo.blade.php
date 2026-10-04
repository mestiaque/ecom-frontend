@php($name = efront()->storeName())
@if(efront()->logoUrl())
    <img src="{{ efront()->logoUrl() }}" alt="{{ $name }}" class="ef-logo-img">
@else
    <div class="blogo">
        <div class="bico"><i class="fas fa-shopping-bag"></i></div>
        <div>
            @php($split = (int) ceil(mb_strlen($name) / 2))
            <div class="bname">{{ mb_substr($name, 0, $split) }}<span>{{ mb_substr($name, $split) }}</span></div>
            @if($tagline = efront()->setting('store_tagline'))
                <div class="bsub d-none d-sm-block">{{ \Illuminate\Support\Str::limit($tagline, 32) }}</div>
            @endif
        </div>
    </div>
@endif
