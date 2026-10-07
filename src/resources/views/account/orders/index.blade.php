@extends('efront::account.layout')

@section('title', 'My Orders')
@section('account_title', 'My Orders')

@section('account')
    <div class="fcard">
        {{-- Desktop: wrapping pills. Mobile: one swipeable row with counts, the active tab scrolled into view (efront.js) --}}
        <div class="ef-status-filter mb-3" data-tab-scroller>
            <a href="{{ route('efront.account.orders') }}" @class(['filtbtn', 'active' => ! request('status')])>All <span class="ef-tab-count">{{ $statusCounts->sum() }}</span></a>
            @foreach($statuses as $status)
                <a href="{{ route('efront.account.orders', ['status' => $status->value]) }}" @class(['filtbtn', 'active' => request('status') === $status->value])>
                    {{ $status->label() }} <span class="ef-tab-count">{{ $statusCounts[$status->value] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
        @include('efront::account.orders.partials.table')
        <div class="ef-pagination mt-4">{{ $orders->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
