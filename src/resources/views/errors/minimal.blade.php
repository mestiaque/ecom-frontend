{{--
    Standalone storefront error page (500, 503, or when the full layout cannot be built).
    No database calls on purpose: the error may come from the database itself.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $page['title'] }} — {{ config('app.name') }}</title>
    <link href="{{ efront_theme()->fontsUrl() }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('efront/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ efront_asset('efront/css/style.css') }}">
    <link rel="stylesheet" href="{{ efront_asset('efront/css/efront.css') }}">
    @if(efront_theme()->isGlass())
        <link rel="stylesheet" href="{{ efront_asset('efront/css/glass.css') }}">
    @endif
    <style id="ef-theme">{!! efront_theme()->css() !!}</style>
</head>
<body class="ef-error-minimal ef-themed {{ efront_theme()->isGlass() ? 'ef-glass' : '' }}">
    @includeWhen(efront_theme()->isGlass(), 'efront::partials.glass-background')
    <section class="ef-error-page">
        <div class="container">
            <div class="ef-error-box">
                <div class="ef-error-icon"><i class="{{ $page['icon'] }}"></i></div>
                <div class="ef-error-code">{{ $status }}</div>
                <h1 class="ef-error-title">{{ $page['title'] }}</h1>
                <p class="ef-error-text">{{ $page['text'] }}</p>
                <div class="ef-error-actions">
                    <button type="button" class="btn-red" onclick="window.location.reload()"><i class="fas fa-redo"></i>Try Again</button>
                    <a href="{{ url('/'.trim((string) config('efront.route_prefix'), '/')) }}" class="ef-btn-outline"><i class="fas fa-home"></i>Go Home</a>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
