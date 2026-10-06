{{-- Storefront error page with the full layout (403, 404, 419, 429). Rendered by Support/ErrorPages. --}}
@extends('efront::layouts.app')

@section('title', $page['title'])

@section('content')
    <section class="ef-error-page">
        <div class="container">
            <div class="ef-error-box">
                <div class="ef-error-icon"><i class="{{ $page['icon'] }}"></i></div>
                <div class="ef-error-code">{{ $status }}</div>
                <h1 class="ef-error-title">{{ $page['title'] }}</h1>
                <p class="ef-error-text">{{ $page['text'] }}</p>

                @if($status === 429 && $retryAfter)
                    <p class="ef-error-wait" data-retry-in="{{ $retryAfter }}"><i class="fas fa-clock me-1"></i>You can try again in <strong>{{ $retryAfter }}</strong> seconds.</p>
                @endif

                @if($status === 404)
                    <form class="ef-error-search" action="{{ route('efront.shop') }}" method="GET" role="search">
                        <i class="fas fa-search"></i>
                        <input type="search" name="q" class="fctrl" placeholder="Search products..." aria-label="Search products">
                    </form>
                @endif

                <div class="ef-error-actions">
                    @if(in_array($status, [419, 429], true))
                        <button type="button" class="btn-red" onclick="window.location.reload()"><i class="fas fa-redo"></i>Try Again</button>
                    @endif
                    @if($status === 403 && ! efront()->customer())
                        <a href="{{ route('efront.account.login') }}" class="btn-red"><i class="fas fa-sign-in-alt"></i>Log In</a>
                    @endif
                    <a href="{{ route('efront.home') }}" class="{{ in_array($status, [419, 429], true) || ($status === 403 && ! efront()->customer()) ? 'ef-btn-outline' : 'btn-red' }}"><i class="fas fa-home"></i>Go Home</a>
                    <a href="{{ route('efront.shop') }}" class="ef-btn-outline"><i class="fas fa-store"></i>Browse Shop</a>
                </div>
            </div>
        </div>
    </section>
@endsection
