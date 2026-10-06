@extends('efront::layouts.app')

@section('title', 'Checkout')

@section('content')
    <x-efront::page-header title="Checkout" :breadcrumbs="[['label' => 'Cart', 'url' => route('efront.cart')], ['label' => 'Checkout', 'url' => null]]" />

    <section class="ef-checkout">
        <div class="container">
            @if($unavailable->isNotEmpty())
                <div class="alert alert-danger">
                    Some items do not have enough stock. <a href="{{ route('efront.cart') }}" class="alert-link">Update your cart</a> before placing the order.
                </div>
            @endif
            @if($errors->has('items') || $errors->has('coupon_code'))
                <div class="alert alert-danger">{{ $errors->first('items') ?: $errors->first('coupon_code') }}</div>
            @endif

            <form method="POST" action="{{ route('efront.checkout.store') }}" data-checkout novalidate>
                @csrf
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="fcard mb-4">
                            <h5 class="ef-summary-title ef-step-title"><span class="ef-step-num">1</span>Shipping Address</h5>
                            @guest('customer')
                                <p class="small text-muted">Already have an account? <a href="{{ route('efront.account.login') }}">Log in</a> to use your saved addresses.</p>
                            @endguest
                            @include('efront::partials.address-picker', ['type' => 'shipping', 'saved' => $shippingAddresses])
                            <div class="mt-3">
                                <label class="flbl" for="customer_email">Email <span class="text-muted fw-normal">(optional, for order updates)</span></label>
                                <input type="email" id="customer_email" name="customer_email" class="fctrl @error('customer_email') is-invalid @enderror" value="{{ old('customer_email', $customer?->email) }}" autocomplete="email">
                                @error('customer_email')<div class="ef-error">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="fcard mb-4">
                            <h5 class="ef-summary-title ef-step-title"><span class="ef-step-num">2</span>Billing Address</h5>
                            <label class="ef-check mb-0">
                                <input type="hidden" name="billing_same" value="0">
                                <input type="checkbox" name="billing_same" value="1" data-billing-same @checked(old('billing_same', '1') === '1')>
                                <span>Same as shipping address</span>
                            </label>
                            <div class="mt-3" data-billing-section @if(old('billing_same', '1') === '1') hidden @endif>
                                @include('efront::partials.address-picker', ['type' => 'billing', 'saved' => $billingAddresses->isNotEmpty() ? $billingAddresses : $shippingAddresses])
                            </div>
                        </div>

                        <div class="fcard mb-4">
                            <h5 class="ef-summary-title ef-step-title"><span class="ef-step-num">3</span>Delivery</h5>
                            <label class="flbl">Delivery Area *</label>
                            <div class="ef-radio-list">
                                @forelse($zones as $zone)
                                    <label class="ef-radio">
                                        <input type="radio" name="shipping_zone_id" value="{{ $zone->id }}" data-charge="{{ $zone->quote['charge'] }}" data-discount="{{ $zone->quote['discount'] }}" data-rule="{{ $zone->quote['rule']?->label }}" @checked((int) old('shipping_zone_id', $zones->first()->id) === $zone->id) required>
                                        <span class="ef-radio-body">
                                            <strong>{{ $zone->name }}</strong>
                                            @if($zone->delivery_time)<small>{{ $zone->delivery_time }}</small>@endif
                                        </span>
                                        <span class="ef-radio-price">
                                            @if($zone->quote['discount'] > 0)<del>{{ ecom_money($zone->quote['before_discount']) }}</del>@endif
                                            {{ $zone->quote['charge'] > 0 ? ecom_money($zone->quote['charge']) : 'Free' }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-muted mb-0">Delivery areas are not set up yet. Please contact us to order.</p>
                                @endforelse
                            </div>
                            @error('shipping_zone_id')<div class="ef-error">{{ $message }}</div>@enderror
                            <label class="flbl mt-3" for="customer_note">Order Note <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea id="customer_note" name="customer_note" rows="2" class="fctrl" placeholder="Anything we should know about delivery?">{{ old('customer_note') }}</textarea>
                        </div>

                        <div class="fcard">
                            <h5 class="ef-summary-title ef-step-title"><span class="ef-step-num">4</span>Payment Method</h5>
                            @php($payIcons = ['cod' => 'fas fa-money-bill-wave', 'bkash' => 'fas fa-mobile-alt', 'nagad' => 'fas fa-mobile-alt', 'sslcommerz' => 'fas fa-credit-card'])
                            <div class="ef-pay-grid">
                                @foreach($paymentMethods as $method)
                                    <label class="ef-pay-tile">
                                        <input type="radio" name="payment_method" value="{{ $method->value }}" @checked(old('payment_method', $paymentMethods[0]->value) === $method->value) data-payment-method>
                                        <span class="ef-pay-icon"><i class="{{ $payIcons[$method->value] ?? 'fas fa-wallet' }}"></i></span>
                                        <span class="ef-pay-name">{{ $method->label() }}</span>
                                        <i class="fas fa-check-circle ef-pay-check"></i>
                                    </label>
                                @endforeach
                            </div>
                            @foreach($paymentMethods as $method)
                                @php($instructions = ecom_setting("payment_{$method->value}_instructions"))
                                @if($instructions || $method->value !== 'cod')
                                    <div class="ef-pay-info mt-3" data-payment-info="{{ $method->value }}" hidden>
                                        @if($instructions)
                                            <p class="mb-2"><i class="fas fa-info-circle me-1"></i>{!! nl2br(e($instructions)) !!}</p>
                                        @endif
                                        @if($method->value !== 'cod')
                                            <label class="flbl" for="trx{{ $method->value }}">Transaction ID <span class="text-muted fw-normal">(if you have paid already)</span></label>
                                            <input type="text" id="trx{{ $method->value }}" name="transaction_id" class="fctrl" value="{{ old('transaction_id') }}" disabled>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                            @error('payment_method')<div class="ef-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="fcard ef-summary ef-sticky">
                            <h5 class="ef-summary-title">Your Order <span class="text-muted fw-normal small">({{ $lines->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $lines->sum('quantity')) }})</span></h5>
                            <div class="ef-check-lines">
                                @foreach($lines as $line)
                                    <div class="ef-check-line">
                                        <span class="ef-check-img">
                                            <img src="{{ $line->image ?? asset('efront/img/banner-img.jpg') }}" alt="" loading="lazy">
                                            <span class="ef-check-qty">{{ $line->quantity }}</span>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-block ef-check-title">{{ $line->product->title }}</span>
                                            @if($line->variant)<small class="text-muted">{{ $line->variant->label }}</small>@endif
                                        </span>
                                        <strong>{{ ecom_money($line->line_total) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                            <div class="ef-sum-row"><span>Subtotal</span><strong>{{ ecom_money($subtotal) }}</strong></div>
                            @if($coupon['discount'] > 0)
                                <div class="ef-sum-row text-success"><span>Coupon ({{ $coupon['coupon']->code }})</span><strong>-{{ ecom_money($coupon['discount']) }}</strong></div>
                            @elseif($coupon['error'])
                                <div class="ef-error mb-2">Coupon not applied: {{ $coupon['error'] }}</div>
                            @endif
                            <div class="ef-sum-row"><span>Delivery <small class="text-success" data-shipping-discount></small></span><strong data-shipping-amount>—</strong></div>
                            @if($lines->every(fn ($line) => $line->product->free_delivery))
                                <div class="ef-track-note is-success py-2 mb-2"><i class="fas fa-truck"></i>Every item in your cart has free delivery.</div>
                            @endif
                            <div class="ef-sum-row ef-sum-total"><span>Total</span><strong data-total-amount data-base="{{ max(0, $subtotal - $coupon['discount']) }}" data-symbol="{{ config('ecom.currency_symbol') }}">{{ ecom_money(max(0, $subtotal - $coupon['discount'])) }}</strong></div>
                            <button type="submit" class="btn-red ef-btn-place-order w-100 justify-content-center mt-3" @disabled($zones->isEmpty() || $unavailable->isNotEmpty())>
                                {{ efront_theme()->buttonContent('place_order') }}
                            </button>
                            <p class="small text-muted text-center mt-2 mb-0"><i class="fas fa-lock me-1"></i>Your information is safe with us.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
