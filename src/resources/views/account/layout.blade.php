@extends('efront::layouts.app')

@section('content')
    @php($customer = efront()->customer())
    {{-- Section values come back HTML-escaped; decode so the header does not escape them twice --}}
    @php($accountTitle = html_entity_decode($__env->yieldContent('account_title'), ENT_QUOTES))
    <x-efront::page-header :title="$accountTitle" :breadcrumbs="[['label' => 'My Account', 'url' => route('efront.account.dashboard')], ['label' => $accountTitle, 'url' => null]]" />

    <section class="ef-account">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="fcard ef-account-nav">
                        <div class="ef-account-user">
                            <a href="{{ route('efront.account.profile') }}" title="Change photo"><x-efront::avatar :customer="$customer" size="lg" /></a>
                            <div class="min-w-0">
                                <strong class="d-block text-truncate">{{ $customer->name }}</strong>
                                <small class="text-muted">{{ $customer->phone }}</small>
                            </div>
                        </div>
                        <nav>
                            @foreach([
                                ['efront.account.dashboard', 'fas fa-gauge', 'Dashboard', 'efront.account.dashboard'],
                                ['efront.account.orders', 'fas fa-box', 'My Orders & Tracking', 'efront.account.orders*'],
                                ['efront.account.wishlist', 'fas fa-heart', 'Wishlist', 'efront.account.wishlist'],
                                ['efront.account.addresses', 'fas fa-map-marker-alt', 'Address Book', 'efront.account.addresses*'],
                                ['efront.account.profile', 'fas fa-user-pen', 'Profile & Password', 'efront.account.profile'],
                            ] as [$routeName, $icon, $label, $pattern])
                                <a href="{{ route($routeName) }}" @class(['active' => request()->routeIs($pattern)])><i class="{{ $icon }}"></i>{{ $label }}</a>
                            @endforeach
                            <form method="POST" action="{{ route('efront.account.logout') }}">
                                @csrf
                                <button type="submit" class="text-danger"><i class="fas fa-sign-out-alt"></i>Logout</button>
                            </form>
                        </nav>
                    </div>
                </div>
                <div class="col-lg-9">
                    @yield('account')
                </div>
            </div>
        </div>
    </section>
@endsection
