{{-- Storefront version of ecom's tracking result (signed link from SMS / the tracking form). --}}
@extends('efront::layouts.app')

@section('title', 'Order '.$order->order_number)

@php
    $phone = $order->customer_phone;
    $maskedPhone = strlen($phone) > 6 ? substr($phone, 0, 3).str_repeat('•', strlen($phone) - 6).substr($phone, -3) : $phone;
@endphp

@section('content')
    <x-efront::page-header :title="'Order '.$order->order_number" :breadcrumbs="[['label' => 'Track Order', 'url' => route('ecom.track.form')], ['label' => $order->order_number, 'url' => null]]" />

    <section class="ef-account">
        <div class="container">
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
                @include('efront::partials.order-tracking', ['history' => $history, 'steps' => $steps])
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ \ME\Efront\Http\Controllers\InvoiceController::url($order) }}" target="_blank" class="ef-btn-outline ef-btn-sm"><i class="fas fa-file-invoice me-1"></i>Invoice</a>
                    <a href="{{ \ME\Efront\Http\Controllers\InvoiceController::url($order, download: true) }}" download class="ef-btn-outline ef-btn-sm"><i class="fas fa-file-pdf me-1"></i>Download PDF</a>
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
                                    <span class="d-block ef-check-title">{{ $item->product_name }}</span>
                                    @if($item->variant_label)<small class="text-muted d-block">{{ $item->variant_label }}</small>@endif
                                    @if($item->warranty_label)
                                        @php($endsAt = $item->warrantyEndsAt())
                                        <small class="d-block {{ $endsAt && $endsAt->isPast() ? 'text-muted' : 'text-success' }}">
                                            <i class="fas fa-shield-alt me-1"></i>{{ $item->warranty_label }}
                                            @if($endsAt) — {{ $endsAt->isPast() ? 'expired on' : 'valid until' }} {{ $endsAt->format('d M Y') }} @else — starts when delivered @endif
                                        </small>
                                    @endif
                                </span>
                                <strong>{{ ecom_money($item->line_total) }}</strong>
                            </div>
                        @endforeach
                        <div class="ef-sum-row mt-3"><span>Subtotal</span><strong>{{ ecom_money($order->subtotal) }}</strong></div>
                        @if((float) $order->discount > 0)
                            <div class="ef-sum-row text-success"><span>Discount</span><strong>-{{ ecom_money($order->discount) }}</strong></div>
                        @endif
                        <div class="ef-sum-row"><span>Delivery @if((float) $order->shipping_discount > 0)<small class="text-success">({{ ecom_money($order->shipping_discount) }} off)</small>@endif</span><strong>{{ (float) $order->shipping_charge > 0 ? ecom_money($order->shipping_charge) : 'Free' }}</strong></div>
                        <div class="ef-sum-row ef-sum-total"><span>Total</span><strong>{{ ecom_money($order->total) }}</strong></div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="fcard h-100">
                        <h5 class="ef-summary-title">Delivery To</h5>
                        <p class="mb-1 fw-semibold">{{ $order->customer_name }}</p>
                        <p class="mb-1 text-muted">{{ $maskedPhone }}</p>
                        <p class="mb-3 text-muted">{{ collect([$order->city, $order->shippingZone?->name])->filter()->implode(' · ') }}</p>
                        <h5 class="ef-summary-title">Payment</h5>
                        <p class="mb-0">{{ $order->payment_method->label() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
