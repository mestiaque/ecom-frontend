@extends('efront::account.layout')

@section('title', 'Order '.$order->order_number)
@section('account_title', 'Order '.$order->order_number)

@section('account')
    <div class="fcard mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h5 class="mb-1">Order {{ $order->order_number }}</h5>
                <small class="text-muted">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-efront::order-status :status="$order->status" />
                <span class="badge rounded-pill text-bg-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
            </div>
        </div>

        @include('efront::partials.order-tracking')

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ \ME\Efront\Http\Controllers\InvoiceController::url($order) }}" target="_blank" class="ef-btn-outline ef-btn-sm"><i class="fas fa-file-invoice me-1"></i>Invoice</a>
            <a href="{{ \ME\Efront\Http\Controllers\InvoiceController::url($order, download: true) }}" download class="ef-btn-outline ef-btn-sm"><i class="fas fa-file-pdf me-1"></i>Download PDF</a>
            @if($order->status === \ME\Ecom\Enums\OrderStatus::Pending)
                <form method="POST" action="{{ route('efront.account.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                    @csrf
                    @method('PATCH')
                    <button class="ef-btn-outline ef-btn-sm ef-btn-danger"><i class="fas fa-times me-1"></i>Cancel order</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="fcard h-100">
                <h5 class="ef-summary-title">Items</h5>
                @foreach($order->items as $item)
                    <div class="ef-check-line">
                        <span class="ef-check-img">
                            <img src="{{ $item->product?->thumbnail ?? asset('efront/img/banner-img.jpg') }}" alt="" loading="lazy">
                            <span class="ef-check-qty">{{ $item->quantity }}</span>
                        </span>
                        <span class="flex-grow-1 min-w-0">
                            @if($item->product)
                                <a href="{{ route('efront.product', $item->product) }}" class="d-block ef-check-title">{{ $item->product_name }}</a>
                            @else
                                <span class="d-block ef-check-title">{{ $item->product_name }}</span>
                            @endif
                            @if($item->variant_label)<small class="text-muted d-block">{{ $item->variant_label }}</small>@endif
                            @if($item->warranty_label)<small class="text-muted d-block"><i class="fas fa-shield-alt me-1"></i>{{ $item->warranty_label }}</small>@endif
                        </span>
                        <strong>{{ ecom_money($item->line_total) }}</strong>
                    </div>
                @endforeach
                <div class="ef-sum-row mt-3"><span>Subtotal</span><strong>{{ ecom_money($order->subtotal) }}</strong></div>
                @if((float) $order->discount > 0)
                    <div class="ef-sum-row text-success"><span>Discount @if($order->coupon_code)({{ $order->coupon_code }})@endif</span><strong>-{{ ecom_money($order->discount) }}</strong></div>
                @endif
                <div class="ef-sum-row"><span>Delivery @if((float) $order->shipping_discount > 0)<small class="text-success">({{ ecom_money($order->shipping_discount) }} off)</small>@endif</span><strong>{{ (float) $order->shipping_charge > 0 ? ecom_money($order->shipping_charge) : 'Free' }}</strong></div>
                <div class="ef-sum-row ef-sum-total"><span>Total</span><strong>{{ ecom_money($order->total) }}</strong></div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="fcard h-100">
                <h5 class="ef-summary-title">Delivery</h5>
                <p class="mb-1 fw-semibold">{{ $order->customer_name }}</p>
                <p class="mb-1">{{ $order->customer_phone }}</p>
                <p class="mb-1">{{ $order->shipping_address }}</p>
                @if($order->city)<p class="mb-1">{{ $order->city }}</p>@endif
                @if($order->billing_address)
                    <h5 class="ef-summary-title mt-3">Billing</h5>
                    <p class="mb-1">{{ $order->billing_address }}</p>
                @endif
                @if($order->shippingZone)<p class="text-muted small mb-3">{{ $order->shippingZone->name }} @if($order->shippingZone->delivery_time) · {{ $order->shippingZone->delivery_time }} @endif</p>@endif
                <h5 class="ef-summary-title mt-3">Payment</h5>
                <p class="mb-1">{{ $order->payment_method->label() }}</p>
                @if(app(\ME\Ecom\Services\Payments\PaymentManager::class)->canPayOnline($order))
                    <a href="{{ \ME\Efront\Http\Controllers\PaymentController::payUrl($order) }}" class="btn-red btn-sm my-2"><i class="fas fa-lock"></i>Pay now</a>
                @endif
                @if($order->customer_note)
                    <h5 class="ef-summary-title mt-3">Note</h5>
                    <p class="mb-0 text-muted">{!! nl2br(e($order->customer_note)) !!}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
