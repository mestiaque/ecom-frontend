<?php

namespace ME\Efront;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use ME\Efront\Http\Middleware\AuthenticateCustomer;
use ME\Efront\Http\Middleware\RedirectIfCustomer;
use ME\Efront\Http\Middleware\TrackOrderForCustomer;
use ME\Efront\Models\Customer;
use ME\Efront\Support\Cart;
use ME\Efront\Support\ErrorPages;
use ME\Efront\Support\Storefront;
use ME\Efront\Support\Theme;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EfrontServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        // Admin pages use metheme's me_prefix(), which metheme defines in its own boot() (it boots after efront)
        $this->app->booted(fn () => $this->loadRoutesFrom(__DIR__.'/routes/admin.php'));
        $this->loadViewsFrom(__DIR__.'/resources/views', 'efront');
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'efront');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        Blade::anonymousComponentPath(__DIR__.'/resources/views/components', 'efront');

        // Storefront look for ecom's public order tracking pages (ecom::tracking.form / show)
        $this->app['view']->prependNamespace('ecom', __DIR__.'/resources/views/overrides/ecom');

        $this->publishes([__DIR__.'/public' => public_path('/')], 'efront-assets');
        $this->publishes([__DIR__.'/Config/config.php' => config_path('efront.php')], 'efront-config');

        $this->configureRateLimiting();

        // Storefront error pages (admin keeps metheme's pages — see Support/ErrorPages)
        $handler = $this->app->make(ExceptionHandler::class);
        if (method_exists($handler, 'renderable')) {
            $handler->renderable(fn (Throwable $e, Request $request) => app(ErrorPages::class)->render($e, $request));
        }

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('efront.auth', AuthenticateCustomer::class);
        $router->aliasMiddleware('efront.guest', RedirectIfCustomer::class);

        // Logged-in customers track orders from their account. Added when ecom's tracking route is
        // matched (before its middleware runs), so it works whatever order the packages boot in.
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            if ($event->route->named('ecom.track.form', 'ecom.track.submit', 'ecom.track.show')) {
                $event->route->middleware(TrackOrderForCustomer::class);
            }
        });

        // Sidebar entries are a numeric array — must array_merge, not mergeConfigFrom
        if (file_exists($sidebar = __DIR__.'/Config/sidebar.php')) {
            Config::set('sidebar', array_merge(
                config('sidebar', []),
                require $sidebar
            ));
        }
    }

    /**
     * One named limiter per action, so each keeps its own counter. A plain "throttle:10,1" shares a
     * single counter per visitor across every throttled route (cart, live search, geo API, ...), which
     * made checkout fail with 429 after browsing. Form posts go back with a message instead of an error page.
     */
    private function configureRateLimiting(): void
    {
        $perMinute = [
            'efront-search' => 120,
            'efront-cart' => 120,
            'efront-checkout' => 15,
            'efront-coupon' => 15,
            'efront-review' => 5,
            'efront-contact' => 5,
            'efront-login' => 10,
            'efront-register' => 10,
            'efront-verify' => 10,
            'efront-resend' => 3,
            'efront-password' => 5,
        ];

        foreach ($perMinute as $name => $max) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($max)
                ->by(($customer = efront()->customer()) ? 'customer:'.$customer->id : 'ip:'.$request->ip())
                ->response(fn (Request $request, array $headers) => self::tooManyAttempts($request, $headers)));
        }
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public static function tooManyAttempts(Request $request, array $headers): Response
    {
        $seconds = (int) ($headers['Retry-After'] ?? 60);
        $message = "Too many attempts. Please wait {$seconds} seconds and try again.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 429, $headers);
        }

        return back()->withInput($request->except(['password', 'password_confirmation', 'current_password']))->with('error', $message)->withHeaders($headers);
    }

    public function register(): void
    {
        require_once __DIR__.'/Support/helpers.php';

        $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'efront');
        $this->mergeConfigFrom(__DIR__.'/Config/theme.php', 'efront_theme');
        $this->mergeConfigFrom(__DIR__.'/Config/theme_presets.php', 'efront_theme_presets');
        $this->mergeConfigFrom(__DIR__.'/Config/permission.php', 'permissions');

        $this->app->scoped(Storefront::class);
        $this->app->scoped(Cart::class);
        $this->app->scoped(Theme::class);

        // Shop customers log in with their own guard (ecom_customers), separate from admin users
        $this->app['config']->set([
            'auth.guards.customer' => ['driver' => 'session', 'provider' => 'customers'],
            'auth.providers.customers' => ['driver' => 'eloquent', 'model' => Customer::class],
            'auth.passwords.customers' => [
                'provider' => 'customers',
                'table' => 'efront_password_reset_tokens',
                'expire' => 60,
                'throttle' => 60,
            ],
        ]);
    }
}
