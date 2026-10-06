{{-- Price, variant options, quantity and cart buttons — product page and quick view. --}}
@php
    $unavailable = $product->has_variants ? $variants->isEmpty() : $product->stock <= 0;
@endphp
<form action="{{ route('efront.cart.store') }}" method="POST" class="ef-purchase" data-purchase-form>
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="variant_id" value="" data-variant-input>
    <script type="application/json" data-variants>@json($variants)</script>

    <div class="ef-price-box">
        <x-efront::price :product="$product" class="ef-price-lg" />
        @if($campaign)
            <span class="ef-deal-tag"><i class="fas fa-bolt me-1"></i>{{ $campaign->title }}</span>
        @endif
    </div>
    @if($campaign)
        <div class="ef-deal-timer" data-countdown="{{ $campaign->ends_at->toIso8601String() }}">
            <i class="fas fa-clock me-1"></i>Offer ends in
            <strong><span data-cd="d">0</span>d <span data-cd="h">00</span>:<span data-cd="m">00</span>:<span data-cd="s">00</span></strong>
        </div>
    @endif

    @foreach($attributes as $attribute)
        <div class="ef-option" data-attribute>
            <div class="ef-option-label">{{ $attribute['name'] }}: <span data-option-selected class="text-muted">Choose</span></div>
            <div class="ef-option-values">
                @foreach($attribute['values'] as $value)
                    @if($attribute['type'] === 'color' && $value->color_code)
                        <button type="button" class="ef-swatch" data-value="{{ $value->id }}" data-label="{{ $value->value }}" title="{{ $value->value }}" style="--swatch:{{ $value->color_code }}"></button>
                    @else
                        <button type="button" class="ef-chip" data-value="{{ $value->id }}" data-label="{{ $value->value }}">{{ $value->value }}</button>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="ef-stock" data-stock>
        @if($unavailable)
            <span class="text-danger"><i class="fas fa-times-circle me-1"></i>Out of stock</span>
        @elseif($product->has_variants)
            <span class="text-muted"><i class="fas fa-info-circle me-1"></i>Choose an option to see availability</span>
        @elseif($product->isLowStock())
            <span class="text-warning"><i class="fas fa-fire me-1"></i>Only {{ $product->stock }} left — order soon</span>
        @else
            <span class="text-success"><i class="fas fa-check-circle me-1"></i>In stock</span>
        @endif
    </div>

    <div class="ef-buy-row">
        <x-efront::qty :max="$product->has_variants ? config('efront.max_quantity') : min($product->stock, config('efront.max_quantity'))" />
        <button type="submit" class="mpaddcart ef-addcart" @disabled($unavailable) data-add-to-cart>
            {{ efront_theme()->buttonContent('add_to_cart') }}
        </button>
        <button type="submit" name="buy_now" value="1" class="ef-buynow" @disabled($unavailable)>
            {{ efront_theme()->buttonContent('buy_now') }}
        </button>
        <x-efront::wishlist-button :product="$product" class="ef-wish-lg" />
    </div>
</form>
