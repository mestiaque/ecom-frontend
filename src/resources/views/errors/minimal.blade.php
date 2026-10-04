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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('efront/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('efront/css/efront.css') }}">
</head>
<body class="ef-error-minimal">
    <section class="ef-error">
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
