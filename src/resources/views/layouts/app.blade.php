<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {{-- <meta name="viewport" content="width=device-width, initial-scale=1"> --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', efront()->setting('store_tagline', efront()->storeName()))">
    @php($pageTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES))
    <title>{{ $pageTitle !== '' ? $pageTitle.' — ' : '' }}{{ efront()->storeName() }}</title>
    @if(efront()->logoUrl())
        <link rel="icon" href="{{ efront()->logoUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ efront_theme()->fontsUrl() }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('efront/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ efront_asset('efront/css/style.css') }}">
    <link rel="stylesheet" href="{{ efront_asset('efront/css/efront.css') }}">
    @if(efront_theme()->isGlass())
        <link rel="stylesheet" href="{{ efront_asset('efront/css/glass.css') }}">
    @endif
    {{-- Admin → Storefront Theme: colours, fonts, buttons, cards --}}
    <style id="ef-theme">{!! efront_theme()->css() !!}</style>
    @stack('styles')
</head>
<body class="@yield('body_class') ef-themed {{ efront_theme()->isGlass() ? 'ef-glass' : '' }}">
    @includeWhen(efront_theme()->isGlass(), 'efront::partials.glass-background')
    @includeWhen(efront_theme()->get('header.topbar'), 'efront::partials.topbar')
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

    @if(efront_theme()->isPreviewing())
        {{-- Only the admin editing the theme sees this; hidden inside the admin preview panel --}}
        <div id="efPreviewBadge" style="position:fixed;left:16px;bottom:16px;z-index:2000;display:flex;align-items:center;gap:10px;padding:8px 10px 8px 14px;border-radius:50px;background:rgba(17,17,17,.85);color:#fff;font-size:.8rem;box-shadow:0 10px 30px rgba(0,0,0,.25);backdrop-filter:blur(8px)">
            <i class="fas fa-eye" style="color:#facc15"></i> Theme preview — not live yet
            <a href="{{ route('efront.admin.theme.preview.exit', ['return' => url()->full()]) }}" style="background:#fff;color:#111;border-radius:50px;padding:3px 10px;text-decoration:none;font-weight:600">Exit</a>
        </div>
        <script>if (window.self !== window.top) document.getElementById('efPreviewBadge').remove();</script>
    @endif

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
    <script src="{{ efront_asset('efront/js/efront.js') }}"></script>
    @stack('scripts')

    @foreach(['success', 'error'] as $type)
        @if(session($type))
            <script>Efront.toast(@json(session($type)), @json($type));</script>
        @endif
    @endforeach
</body>
</html>
