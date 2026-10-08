@php($customer = efront()->customer())
<nav class="navbar navbar-expand-lg" id="nav">
    <div class="container">
        <a class="navbar-brand" href="{{ route('efront.home') }}">@include('efront::partials.logo')</a>

        <div class="d-flex align-items-center gap-1 order-lg-last">
            <button id="navSearchBtn" type="button" title="Search" data-search-open><i class="{{ efront_theme()->icon('search') }}"></i></button>
            <a href="{{ $customer ? route('efront.account.wishlist') : route('efront.account.login') }}" class="ef-navicon d-none d-sm-inline-flex" title="Wishlist">
                <i class="{{ efront_theme()->icon('wishlist') }}"></i>
                <span class="ef-badge" data-wishlist-count @if(! count(efront()->wishlistIds())) hidden @endif>{{ count(efront()->wishlistIds()) }}</span>
            </a>

            <button type="button" class="ef-navicon ef-carticon" data-bs-toggle="offcanvas" data-bs-target="#miniCart" title="Cart">
                <i class="{{ efront_theme()->icon('cart') }}"></i>
                <span class="ef-badge" data-cart-count @if(! efront()->cartCount()) hidden @endif>{{ efront()->cartCount() }}</span>
            </button>
            <div class="dropdown ef-nav-account">
                <a href="#" class="ef-navicon @if($customer) ef-navavatar @endif" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="{{ $customer ? $customer->name : 'Account' }}">
                    @if($customer)
                        <x-efront::avatar :customer="$customer" />
                    @else
                        <i class="{{ efront_theme()->icon('account') }}"></i>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end ef-dropdown">
                    @if($customer)
                        <li class="dropdown-header ef-dropdown-user">
                            <x-efront::avatar :customer="$customer" />
                            <span class="min-w-0"><strong class="d-block text-truncate">{{ $customer->name }}</strong><small class="text-muted">{{ $customer->phone }}</small></span>
                        </li>
                        <li><a class="dropdown-item" href="{{ route('efront.account.dashboard') }}"><i class="fas fa-gauge me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="{{ route('efront.account.orders') }}"><i class="fas fa-box me-2"></i>My Orders</a></li>
                        <li><a class="dropdown-item" href="{{ route('efront.account.wishlist') }}"><i class="fas fa-heart me-2"></i>Wishlist</a></li>
                        <li><a class="dropdown-item" href="{{ route('efront.account.addresses') }}"><i class="fas fa-map-marker-alt me-2"></i>Addresses</a></li>
                        <li><a class="dropdown-item" href="{{ route('efront.account.profile') }}"><i class="fas fa-user-pen me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('efront.account.logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                            </form>
                        </li>
                    @else
                        <li><a class="dropdown-item" href="{{ route('efront.account.login') }}"><i class="fas fa-sign-in-alt me-2"></i>Login</a></li>
                        <li><a class="dropdown-item" href="{{ route('efront.account.register') }}"><i class="fas fa-user-plus me-2"></i>Create account</a></li>
                    @endif
                </ul>
            </div>
            <button class="navbar-toggler border-0 ms-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#navmenu" aria-controls="navmenu" aria-label="Menu">
                <i class="{{ efront_theme()->icon('menu') }} ef-burger-open" style="color:var(--primary);font-size:1.35rem;"></i>
                <i class="fas fa-times ef-burger-close" style="color:var(--primary);font-size:1.35rem;"></i>
            </button>
        </div>

        {{-- Desktop: the normal menu bar. Mobile (< lg): a drawer from the right, below the navbar (offcanvas-lg) --}}
        <div class="offcanvas offcanvas-end offcanvas-lg ef-navdrawer" tabindex="-1" id="navmenu" aria-label="Menu">
            <div class="offcanvas-header">
                <a href="{{ route('efront.home') }}" class="ef-navdrawer-brand">@include('efront::partials.logo')</a>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#navmenu" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('efront.home')) active @endif" href="{{ route('efront.home') }}"><i class="fas fa-house ef-navdrawer-icon"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('efront.shop')) active @endif" href="{{ route('efront.shop') }}"><i class="fas fa-store ef-navdrawer-icon"></i>Shop</a></li>
                @if(efront()->menuCategories()->isNotEmpty())
                    <li class="nav-item dropdown ef-mega">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('efront.category')) active @endif" href="#" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside" aria-expanded="false"><i class="fas fa-layer-group ef-navdrawer-icon"></i>Categories <i class="fas fa-chevron-down ef-mega-caret"></i></a>
                        <div class="dropdown-menu ef-dropdown ef-megamenu">
                            <div class="row g-3">
                                @foreach(efront()->menuCategories() as $menuCategory)
                                    <div class="col-6 col-lg-3">
                                        <a href="{{ route('efront.category', $menuCategory) }}" class="ef-mega-title">{{ $menuCategory->name }}</a>
                                        @foreach($menuCategory->children->take(6) as $child)
                                            <a href="{{ route('efront.category', $child) }}" class="ef-mega-link">{{ $child->name }}</a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </li>
                @endif
                <li class="nav-item"><a class="nav-link" href="{{ route('efront.shop', ['sort' => 'popular']) }}"><i class="fas fa-fire ef-navdrawer-icon"></i>Best Sellers</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('efront.contact')) active @endif" href="{{ route('efront.contact') }}"><i class="fas fa-envelope ef-navdrawer-icon"></i>Contact</a></li>
            </ul>

            {{-- Drawer only: account shortcuts and hotline --}}
            <div class="ef-navdrawer-extra d-lg-none">
                <div class="ef-navdrawer-label">My account</div>
                @if($customer)
                    <a href="{{ route('efront.account.dashboard') }}"><i class="fas fa-gauge"></i>Dashboard</a>
                    <a href="{{ route('efront.account.orders') }}"><i class="fas fa-box"></i>My Orders</a>
                    <a href="{{ route('efront.account.wishlist') }}"><i class="fas fa-heart"></i>Wishlist</a>
                @else
                    <a href="{{ route('efront.account.login') }}"><i class="fas fa-sign-in-alt"></i>Login</a>
                    <a href="{{ route('efront.account.register') }}"><i class="fas fa-user-plus"></i>Create account</a>
                @endif
                <a href="{{ route('ecom.track.form') }}"><i class="fas fa-location-crosshairs"></i>Track Order</a>
                @if($phone = efront()->setting('store_phone'))
                    <a href="tel:{{ preg_replace('~[^0-9+]~', '', $phone) }}" class="ef-navdrawer-call"><i class="fas fa-phone"></i>Call {{ $phone }}</a>
                @endif
            </div>
            </div>
        </div>
    </div>
</nav>
