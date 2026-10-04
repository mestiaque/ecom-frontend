@extends('efront::account.layout')

@section('title', 'My Orders')
@section('account_title', 'My Orders')

@section('account')
    <div class="fcard">
        <div class="ef-status-filter mb-3">
            <a href="{{ route('efront.account.orders') }}" @class(['filtbtn', 'active' => ! request('status')])>All</a>
            @foreach($statuses as $status)
                <a href="{{ route('efront.account.orders', ['status' => $status->value]) }}" @class(['filtbtn', 'active' => request('status') === $status->value])>{{ $status->label() }}</a>
            @endforeach
        </div>
        @include('efront::account.orders.partials.table')
        <div class="ef-pagination mt-4">{{ $orders->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
