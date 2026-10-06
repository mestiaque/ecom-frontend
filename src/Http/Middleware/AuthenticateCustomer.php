<?php

namespace ME\Efront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront pages that need a logged-in customer. Blocked customers are logged out.
 */
class AuthenticateCustomer
{
    /** Session key: the storefront page to open after login / registration */
    public const INTENDED = 'efront.intended';

    /**
     * The saved storefront page, or $default. Admin URLs are never followed from a customer login.
     */
    public static function pullIntended(Request $request, string $default): string
    {
        $url = (string) $request->session()->pull(self::INTENDED, '');
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $admin = '/'.trim(me_prefix(), '/');
        $sameHost = parse_url($url, PHP_URL_HOST) === $request->getHost();

        return $url !== '' && $sameHost && $path !== $admin && ! str_starts_with($path, $admin.'/') ? $url : $default;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer?->is_blocked) {
            Auth::guard('customer')->logout();
            $customer = null;
        }

        if (! $customer) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please log in first.', 'login' => route('efront.account.login')], 401);
            }

            // Own key, not Laravel's "url.intended": that one is shared with the admin login (metheme)
            if ($request->isMethod('GET')) {
                $request->session()->put(self::INTENDED, $request->fullUrl());
            }

            return redirect()->route('efront.account.login');
        }

        return $next($request);
    }
}
