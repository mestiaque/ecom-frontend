<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', efront()->setting('store_tagline', efront()->storeName()))">
    @php($pageTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES))
    <title>{{ $pageTitle !== '' ? $pageTitle.' — ' : '' }}{{ efront()->storeName() }}</title>
    @if(efront()->logoUrl())
        <link rel="icon" href="{{ efront()->logoUrl() }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('efront/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/efront.css') }}">
    @stack('styles')
</head>
<body class="@yield('body_class')">
    @include('efront::partials.topbar')
    @include('efront::partials.navbar')
    @include('efront::partials.search')

    <main id="main">
        @yield('content')
    </main>

    @include('efront::partials.footer')

    {{-- Mini cart (offcanvas) --}}
    <div class="offcanvas offcanvas-end ef-offcart" tabindex="-1" id="miniCart" aria-labelledby="miniCartLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="miniCartLabel"><i class="fas fa-shopping-bag me-2"></i>Your Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" id="miniCartBody">
            @include('efront::partials.mini-cart', ['cart' => app(\ME\Efront\Support\Cart::class)])
        </div>
    </div>

    {{-- Product quick view (filled by AJAX) --}}
    <div id="menuPop" class="ef-quickview" aria-hidden="true">
        <div class="mpbox">
            <button class="mpclose" type="button" data-qv-close aria-label="Close"><i class="fas fa-times"></i></button>
            <div id="qvBody"></div>
        </div>
    </div>

    <div class="ef-toasts" id="efToasts" aria-live="polite"></div>

    <button id="btt" type="button" aria-label="Back to top"><i class="fas fa-chevron-up"></i></button>

    <script>
        window.Efront = {
            csrf: @json(csrf_token()),
            loginUrl: @json(route('efront.account.login')),
            cartUrl: @json(route('efront.cart.store')),
            miniCartUrl: @json(route('efront.cart.mini')),
            suggestUrl: @json(route('efront.search.suggest')),
            wishlistUrl: @json(url(trim(config('efront.route_prefix').'/wishlist', '/'))),
            geoUrl: @json(url('api/geo')),
        };
    </script>
    <script src="{{ asset('efront/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('efront/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('efront/js/aos.js') }}"></script>
    <script src="{{ asset('efront/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('efront/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('efront/js/efront.js') }}"></script>
    @stack('scripts')

    @foreach(['success', 'error'] as $type)
        @if(session($type))
            <script>Efront.toast(@json(session($type)), @json($type));</script>
        @endif
    @endforeach
</body>
</html>
