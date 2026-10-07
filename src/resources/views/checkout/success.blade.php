@extends('efront::layouts.app')

@section('title', 'Order Placed')

@section('content')
    <section class="ef-success">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="fcard text-center">
                        <div class="ef-success-icon"><i class="fas fa-check"></i></div>
                        <span class="slbl">Thank you!</span>
                        <h2 class="stitle">Your order has been <span>placed</span></h2>
                        <p class="sdesc">We have received your order and will call you at <strong>{{ $order->customer_phone }}</strong> to confirm it.</p>

                        @php($paidTry = $order->transactions->firstWhere('status', 'success'))
                        @if($canPayOnline)
                            <div class="ef-pay-pending text-start mb-4">
                                <div class="d-flex gap-3 align-items-start">
                                    <i class="fas fa-hourglass-half fa-lg mt-1"></i>
                                    <div class="flex-grow-1">
                                        <strong class="d-block">Payment not completed yet</strong>
                                        <small>Your order is saved. Pay {{ ecom_money($order->total - $order->paidAmount()) }} with {{ $order->payment_method->label() }} now, or pay the rider in cash when the parcel arrives.</small>
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <a href="{{ \ME\Efront\Http\Controllers\PaymentController::payUrl($order) }}" class="btn-red"><i class="fas fa-lock"></i>Pay with {{ $order->payment_method->label() }}</a>
                                            <form method="POST" action="{{ \ME\Efront\Http\Controllers\PaymentController::codUrl($order) }}">
                                                @csrf
                                                <button type="submit" class="ef-btn-outline"><i class="fas fa-money-bill-wave me-1"></i>Cash on Delivery instead</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($paidTry)
                            <div class="ef-pay-done text-start mb-4">
                                <i class="fas fa-circle-check fa-lg"></i>
                                <div>
                                    <strong class="d-block">Paid {{ ecom_money($paidTry->amount) }} with {{ \ME\Ecom\Enums\PaymentMethod::from($paidTry->method)->label() }}</strong>
                                    <small>Transaction ID: {{ $paidTry->trx_id }} @if($paidTry->gateway_response['sandbox'] ?? false)<span class="ef-pay-sandbox ms-1">Sandbox test</span>@endif</small>
                                </div>
                            </div>
                        @endif

                        <div class="ef-success-box">
                            <div><small>Order number</small><strong>{{ $order->order_number }}</strong></div>
                            <div><small>Total</small><strong>{{ ecom_money($order->total) }}</strong></div>
                            <div><small>Payment</small><strong>{{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</strong></div>
                        </div>

                        <div class="text-start mt-4">
                            @foreach($order->items as $item)
                                <div class="ef-sum-row">
                                    <span>{{ $item->product_name }} @if($item->variant_label)<small class="text-muted">({{ $item->variant_label }})</small>@endif × {{ $item->quantity }}</span>
                                    <strong>{{ ecom_money($item->line_total) }}</strong>
                                </div>
                            @endforeach
                            @if((float) $order->discount > 0)
                                <div class="ef-sum-row text-success"><span>Discount</span><strong>-{{ ecom_money($order->discount) }}</strong></div>
                            @endif
                            <div class="ef-sum-row"><span>Delivery @if((float) $order->shipping_discount > 0)<small class="text-success">({{ ecom_money($order->shipping_discount) }} off)</small>@endif</span><strong>{{ (float) $order->shipping_charge > 0 ? ecom_money($order->shipping_charge) : 'Free' }}</strong></div>
                            <div class="ef-sum-row ef-sum-total"><span>Total</span><strong>{{ ecom_money($order->total) }}</strong></div>
                        </div>

                        <div class="d-flex justify-content-center flex-wrap gap-3 mt-4">
                            <a href="{{ $order->trackingPageUrl() }}" class="btn-red"><i class="fas fa-location-crosshairs"></i>Track Order</a>
                            <a href="{{ \ME\Efront\Http\Controllers\InvoiceController::url($order) }}" target="_blank" class="ef-btn-outline"><i class="fas fa-file-invoice me-1"></i>Invoice</a>
                            @if(efront()->customer() && $order->customer_id === efront()->customer()->id)
                                <a href="{{ route('efront.account.orders.show', $order) }}" class="ef-btn-outline">View in My Orders</a>
                            @endif
                            <a href="{{ route('efront.shop') }}" class="ef-btn-outline">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
