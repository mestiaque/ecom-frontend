@php($lines = $cart->lines())
@if($lines->isEmpty())
    <x-efront::empty icon="fas fa-shopping-bag" title="Your cart is empty" text="Browse the shop and add something you like.">
        <a href="{{ route('efront.shop') }}" class="btn-red mt-2"><i class="fas fa-store"></i>Start Shopping</a>
    </x-efront::empty>
@else
    <div class="ef-mini-lines">
        @foreach($lines as $line)
            <div class="ef-mini-line">
                <a href="{{ route('efront.product', $line->product) }}" class="ef-mini-img">
                    <img src="{{ $line->image ?? asset('efront/img/banner-img.jpg') }}" alt="{{ $line->product->title }}" loading="lazy">
                </a>
                <div class="flex-grow-1 min-w-0">
                    <a href="{{ route('efront.product', $line->product) }}" class="ef-mini-title">{{ $line->product->title }}</a>
                    @if($line->variant)
                        <div class="ef-mini-variant">{{ $line->variant->label }}</div>
                    @endif
                    <div class="ef-mini-price">{{ $line->quantity }} × {{ ecom_money($line->unit_price) }}</div>
                </div>
                <button type="button" class="ef-mini-remove" data-cart-remove="{{ route('efront.cart.destroy', $line->key) }}" title="Remove"><i class="fas fa-trash-alt"></i></button>
            </div>
        @endforeach
    </div>
    <div class="ef-mini-foot">
        <div class="d-flex justify-content-between mb-3">
            <span class="fw-semibold">Subtotal</span>
            <strong class="text-primary-ef">{{ ecom_money($cart->subtotal()) }}</strong>
        </div>
        <div class="d-grid gap-2">
            <a href="{{ route('efront.checkout') }}" class="btn-red ef-btn-checkout justify-content-center">{{ efront_theme()->buttonContent('checkout') }}</a>
            <a href="{{ route('efront.cart') }}" class="ef-btn-outline justify-content-center">View Cart</a>
        </div>
    </div>
@endif
