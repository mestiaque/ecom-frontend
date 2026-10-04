@php($customer = efront()->customer())
<nav class="navbar navbar-expand-lg" id="nav">
    <div class="container">
        <a class="navbar-brand" href="{{ route('efront.home') }}">@include('efront::partials.logo')</a>

        <div class="d-flex align-items-center gap-1 order-lg-last">
            <button id="navSearchBtn" type="button" title="Search" data-search-open><i class="fas fa-search"></i></button>
            <a href="{{ $customer ? route('efront.account.wishlist') : route('efront.account.login') }}" class="ef-navicon d-none d-sm-inline-flex" title="Wishlist">
                <i class="far fa-heart"></i>
                <span class="ef-badge" data-wishlist-count @if(! count(efront()->wishlistIds())) hidden @endif>{{ count(efront()->wishlistIds()) }}</span>
            </a>
            <div class="dropdown">
                <a href="#" class="ef-navicon @if($customer) ef-navavatar @endif" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $customer ? $customer->name : 'Account' }}">
                    @if($customer)
                        <x-efront::avatar :customer="$customer" />
                    @else
                        <i class="far fa-user"></i>
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
            <button type="button" class="ef-navicon ef-carticon" data-bs-toggle="offcanvas" data-bs-target="#miniCart" title="Cart">
                <i class="fas fa-shopping-bag"></i>
                <span class="ef-badge" data-cart-count @if(! efront()->cartCount()) hidden @endif>{{ efront()->cartCount() }}</span>
            </button>
            <button class="navbar-toggler border-0 ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu" aria-label="Menu">
                <i class="fas fa-bars" style="color:var(--primary);font-size:1.35rem;"></i>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navmenu">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('efront.home')) active @endif" href="{{ route('efront.home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('efront.shop')) active @endif" href="{{ route('efront.shop') }}">Shop</a></li>
                @if(efront()->menuCategories()->isNotEmpty())
                    <li class="nav-item dropdown ef-mega">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('efront.category')) active @endif" href="#" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside" aria-expanded="false">Categories <i class="fas fa-chevron-down ef-mega-caret"></i></a>
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
                <li class="nav-item"><a class="nav-link" href="{{ route('efront.shop', ['sort' => 'popular']) }}">Best Sellers</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('efront.contact')) active @endif" href="{{ route('efront.contact') }}">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>
