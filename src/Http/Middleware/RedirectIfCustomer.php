<?php

namespace ME\Efront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login / register pages: a logged-in customer goes to the account dashboard.
 */
class RedirectIfCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('efront.account.dashboard');
        }

        return $next($request);
    }
}
