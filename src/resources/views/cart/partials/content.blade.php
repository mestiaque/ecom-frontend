@if($lines->isEmpty())
    <x-efront::empty icon="fas fa-shopping-bag" title="Your cart is empty" text="Looks like you have not added anything yet.">
        <a href="{{ route('efront.shop') }}" class="btn-red mt-2"><i class="fas fa-store"></i>Continue Shopping</a>
    </x-efront::empty>
@else
    @php($total = max(0, $subtotal - $coupon['discount']))
    <div class="row g-4">
        <div class="col-lg-8">
            @if($shipsFree)
                <div class="ef-freeship"><i class="fas fa-check-circle me-2 text-success"></i>Every item in your cart has <strong>free delivery</strong>.</div>
            @elseif(! is_null($freeShippingMin))
                <div class="ef-freeship">
                    @if($freeShippingLeft > 0)
                        <i class="fas fa-truck me-2"></i>Add <strong>{{ ecom_money($freeShippingLeft) }}</strong> more for <strong>free delivery</strong>.
                    @else
                        <i class="fas fa-check-circle me-2"></i>You get <strong>free delivery</strong> on this order!
                    @endif
                    <div class="ef-bar mt-2"><div style="width:{{ $freeShippingMin > 0 ? min(100, round($subtotal / $freeShippingMin * 100)) : 100 }}%"></div></div>
                </div>
            @endif

            <div class="fcard p-0 overflow-hidden">
                @foreach($lines as $line)
                    <div class="ef-cart-line @if($line->stock < $line->quantity) is-short @endif">
                        <a href="{{ route('efront.product', $line->product) }}" class="ef-cart-img">
                            <img src="{{ $line->image ?? asset('efront/img/banner-img.jpg') }}" alt="{{ $line->product->title }}" loading="lazy">
                        </a>
                        <div class="ef-cart-info">
                            <a href="{{ route('efront.product', $line->product) }}" class="ef-cart-title">{{ $line->product->title }}</a>
                            @if($line->variant)
                                <div class="ef-cart-variant">{{ $line->variant->label }}</div>
                            @endif
                            <div class="ef-cart-unit">
                                {{ ecom_money($line->unit_price) }}
                                @if($line->regular_price > $line->unit_price)
                                    <del>{{ ecom_money($line->regular_price) }}</del>
                                @endif
                            </div>
                            @if($line->product->free_delivery)
                                <div class="ef-ship-note"><i class="fas fa-truck"></i>Free delivery</div>
                            @elseif((float) $line->product->delivery_charge_adjustment > 0)
                                <div class="ef-ship-note is-extra"><i class="fas fa-truck"></i>+{{ ecom_money($line->product->delivery_charge_adjustment) }} delivery per item</div>
                            @endif
                            @if($line->stock < $line->quantity)
                                <div class="ef-error">{{ $line->stock > 0 ? "Only {$line->stock} left in stock." : 'Out of stock — please remove it.' }}</div>
                            @endif
                        </div>
                        <form class="ef-cart-qty" action="{{ route('efront.cart.update', $line->key) }}" method="POST" data-cart-update>
                            @csrf
                            @method('PATCH')
                            <x-efront::qty :value="$line->quantity" :max="max(1, min($line->stock, config('efront.max_quantity')))" />
                        </form>
                        <div class="ef-cart-total">{{ ecom_money($line->line_total) }}</div>
                        <button type="button" class="ef-cart-remove" data-cart-remove="{{ route('efront.cart.destroy', $line->key) }}" title="Remove"><i class="fas fa-times"></i></button>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('efront.shop') }}" class="ef-link-more mt-3 d-inline-block"><i class="fas fa-arrow-left me-1"></i> Continue shopping</a>
        </div>

        <div class="col-lg-4">
            <div class="fcard ef-summary">
                <h5 class="ef-summary-title">Order Summary</h5>
                <div class="ef-sum-row"><span>Subtotal ({{ $cart->count() }} {{ \Illuminate\Support\Str::plural('item', $cart->count()) }})</span><strong>{{ ecom_money($subtotal) }}</strong></div>

                @if($coupon['coupon'] && ! $coupon['error'])
                    <div class="ef-sum-row text-success">
                        <span>Coupon <strong>{{ $coupon['coupon']->code }}</strong>
                            <button type="button" class="ef-link-btn" data-coupon-remove="{{ route('efront.cart.coupon.remove') }}">remove</button>
                        </span>
                        <strong>-{{ ecom_money($coupon['discount']) }}</strong>
                    </div>
                @else
                    @if($coupon['error'])
                        <div class="ef-error mb-2">{{ $coupon['error'] }}</div>
                    @endif
                    <form class="ef-coupon" action="{{ route('efront.cart.coupon') }}" method="POST" data-coupon-form>
                        @csrf
                        <input type="text" name="coupon_code" class="fctrl" placeholder="Coupon code" value="{{ old('coupon_code') }}" aria-label="Coupon code">
                        <button type="submit" class="ef-btn-outline">Apply</button>
                    </form>
                    @error('coupon_code')<div class="ef-error mt-1">{{ $message }}</div>@enderror
                @endif

                <div class="ef-sum-row text-muted"><span>Delivery</span><span>Calculated at checkout</span></div>
                <div class="ef-sum-row ef-sum-total"><span>Total</span><strong>{{ ecom_money($total) }}</strong></div>
                <a href="{{ route('efront.checkout') }}" class="btn-red ef-btn-checkout w-100 justify-content-center mt-3">{{ efront_theme()->buttonContent('checkout') }}</a>
            </div>
        </div>
    </div>
@endif
