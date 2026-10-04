@if($orders->isEmpty())
    <x-efront::empty icon="fas fa-box-open" title="No orders yet" text="When you place an order it will show up here.">
        <a href="{{ route('efront.shop') }}" class="btn-red mt-2"><i class="fas fa-store"></i>Start Shopping</a>
    </x-efront::empty>
@else
    <div class="table-responsive">
        <table class="table ef-table align-middle mb-0">
            <thead>
                <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td class="fw-semibold">{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                        <td>{{ $order->items_count }}</td>
                        <td class="fw-semibold">{{ ecom_money($order->total) }}</td>
                        <td><x-efront::order-status :status="$order->status" /></td>
                        <td class="text-end"><a href="{{ route('efront.account.orders.show', $order) }}" class="ef-btn-outline ef-btn-sm">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
