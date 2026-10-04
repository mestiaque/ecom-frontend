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

            return redirect()->guest(route('efront.account.login'));
        }

        return $next($request);
    }
}
