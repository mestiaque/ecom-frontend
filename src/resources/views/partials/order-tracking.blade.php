{{--
    Order progress: status message, steps with dates, courier and status history.
    Needs $order, $history (status notes, oldest first) and $steps (OrderStatus list).
    Used by the account order page and the public tracking page.
--}}
@php
    $status = $order->status;
    $stopped = in_array($status, [\ME\Ecom\Enums\OrderStatus::Cancelled, \ME\Ecom\Enums\OrderStatus::Returned], true);
    $reachedAt = $history->groupBy(fn ($note) => $note->status->value)->map(fn ($notes) => $notes->first()->created_at);
    // A cancelled / returned order shows progress up to the last normal step it reached
    $reachedIndex = collect($steps)->keys()->filter(fn ($i) => $reachedAt->has($steps[$i]->value))->max() ?? 0;
    $currentIndex = $stopped ? $reachedIndex : (int) array_search($status, $steps, true);
    $labels = ['pending' => 'Order placed', 'confirmed' => 'Confirmed', 'processing' => 'Packed', 'shipped' => 'On the way', 'delivered' => 'Delivered'];
@endphp

@if($status === \ME\Ecom\Enums\OrderStatus::Cancelled)
    <div class="ef-track-note is-danger"><i class="fas fa-times-circle"></i>This order was cancelled{{ $order->cancelled_at ? ' on '.$order->cancelled_at->format('d M Y') : '' }}. Any payment made will be refunded.</div>
@elseif($status === \ME\Ecom\Enums\OrderStatus::Returned)
    <div class="ef-track-note is-muted"><i class="fas fa-undo"></i>This order was returned. Any payment made will be refunded.</div>
@elseif($status === \ME\Ecom\Enums\OrderStatus::Delivered)
    <div class="ef-track-note is-success"><i class="fas fa-check-circle"></i>Delivered on {{ $order->delivered_at?->format('d M Y, h:i A') }}. Thank you for shopping with us!</div>
@elseif($order->shippingZone?->delivery_time)
    <div class="ef-track-note"><i class="far fa-clock"></i>Usually delivered in {{ $order->shippingZone->delivery_time }} after confirmation.</div>
@endif

<div @class(['ef-steps', 'is-stopped' => $stopped]) style="--ef-progress: {{ count($steps) > 1 ? round($currentIndex / (count($steps) - 1) * 84) : 0 }}%">
    @foreach($steps as $index => $step)
        <div @class(['ef-step', 'done' => $index <= $currentIndex, 'current' => $index === $currentIndex && ! $stopped])>
            <span class="ef-step-dot"><i class="{{ $step->icon() }}"></i></span>
            <span class="ef-step-label">{{ $labels[$step->value] }}</span>
            @if($reachedAt->has($step->value))
                <small>{{ $reachedAt[$step->value]->format('d M, h:i A') }}</small>
            @endif
        </div>
    @endforeach
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="ef-track-box h-100">
            <h6><i class="fas fa-truck"></i>Courier</h6>
            @if($order->courier)
                <div class="ef-track-kv"><span>Courier</span><strong>{{ config("ecom.couriers.{$order->courier}.label", $order->courier) }}</strong></div>
                <div class="ef-track-kv"><span>Tracking ID</span><strong class="font-monospace">{{ $order->tracking_id }}</strong></div>
                @if($courierUrl = $order->trackingUrl())
                    <a href="{{ $courierUrl }}" target="_blank" rel="noopener" class="ef-btn-outline ef-btn-sm mt-2"><i class="fas fa-external-link-alt me-1"></i>Track on courier website</a>
                @endif
            @elseif($stopped)
                <p class="mb-0 text-muted small">No delivery for this order.</p>
            @else
                <p class="mb-0 text-muted small">Your parcel has not been handed to the courier yet. You will get the tracking ID by SMS.</p>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="ef-track-box h-100">
            <h6><i class="fas fa-history"></i>History</h6>
            <ul class="ef-timeline">
                @foreach($history->reverse() as $entry)
                    <li @class(['is-latest' => $loop->first])>
                        <span class="ef-timeline-dot text-bg-{{ $entry->status->color() }}"><i class="{{ $entry->status->icon() }}"></i></span>
                        <span class="ef-timeline-body">
                            <strong>{{ $labels[$entry->status->value] ?? $entry->status->label() }}</strong>
                            <small>{{ $entry->created_at->format('d M Y, h:i A') }}</small>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
