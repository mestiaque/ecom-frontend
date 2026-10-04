{{-- Storefront version of ecom's tracking form (efront prepends this folder to the "ecom" view namespace). --}}
@extends('efront::layouts.app')

@section('title', 'Track Order')

@section('content')
    <x-efront::page-header title="Track your order" :breadcrumbs="[['label' => 'Track Order', 'url' => null]]" />

    <section class="ef-auth">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="fcard ef-auth-card">
                        <div class="text-center mb-4">
                            <div class="ef-auth-icon"><i class="fas fa-shipping-fast"></i></div>
                            <h2 class="ef-auth-title">Where is my order?</h2>
                            <p class="text-muted mb-0">Enter the order number from your SMS or invoice and the mobile number you ordered with.</p>
                        </div>

                        <form method="POST" action="{{ route('ecom.track.submit') }}" novalidate>
                            @csrf
                            @if($errors->any())
                                <div class="ef-track-note is-danger"><i class="fas fa-exclamation-circle"></i>{{ $errors->first() }}</div>
                            @endif
                            <div class="mb-3">
                                <label class="flbl" for="order_number">Order number</label>
                                <input type="text" id="order_number" name="order_number" value="{{ old('order_number', request('order')) }}" class="fctrl" placeholder="e.g. {{ ecom_setting('order_prefix', 'ORD-') }}000123" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label class="flbl" for="phone">Mobile number</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="fctrl" placeholder="01XXXXXXXXX" required>
                            </div>
                            <button type="submit" class="btn-red w-100 justify-content-center"><i class="fas fa-search"></i>Track Order</button>
                        </form>

                        <p class="text-center small text-muted mt-4 mb-0">
                            Have an account? <a href="{{ route('efront.account.login') }}" class="fw-semibold">Log in</a> to see all your orders and their tracking.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
