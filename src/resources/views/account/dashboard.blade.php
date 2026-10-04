@extends('efront::account.layout')

@section('title', 'My Account')
@section('account_title', 'Dashboard')

@section('account')
    <div class="fcard mb-4 ef-welcome">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <x-efront::avatar :customer="$customer" size="lg" />
            <div class="flex-grow-1">
                <h4 class="mb-1">Hello, {{ $customer->name }} 👋</h4>
                <p class="text-muted mb-0">From here you can follow your orders, manage your wishlist and update your details.</p>
            </div>
        </div>
        <div class="ef-welcome-address">
            <i class="fas fa-map-marker-alt"></i>
            @if($defaultAddress)
                <span><strong>Default delivery address:</strong> {{ $defaultAddress->full }}</span>
                <a href="{{ route('efront.account.addresses') }}">Change</a>
            @else
                <span class="text-muted">No delivery address saved yet.</span>
                <a href="{{ route('efront.account.addresses.create') }}">Add address</a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['fas fa-box', 'Total Orders', $stats['orders'], 'r', route('efront.account.orders')],
            ['fas fa-truck', 'In Progress', $stats['active'], 'y', route('efront.account.orders')],
            ['fas fa-wallet', 'Total Spent', ecom_money($stats['spent']), 'g', route('efront.account.orders')],
            ['fas fa-heart', 'Wishlist', $stats['wishlist'], 'r', route('efront.account.wishlist')],
        ] as [$icon, $label, $value, $tone, $url])
            <div class="col-6 col-xl-3">
                <a href="{{ $url }}" class="ef-stat">
                    <span class="ftico {{ $tone }}"><i class="{{ $icon }}"></i></span>
                    <span><strong>{{ $value }}</strong><small>{{ $label }}</small></span>
                </a>
            </div>
        @endforeach
    </div>

    <div class="fcard">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="ef-summary-title mb-0">Recent Orders</h5>
            <a href="{{ route('efront.account.orders') }}" class="ef-link-more">View all <i class="fas fa-arrow-right"></i></a>
        </div>
        @include('efront::account.orders.partials.table', ['orders' => $recentOrders])
    </div>
@endsection
