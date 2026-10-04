<?php

namespace ME\Efront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ME\Ecom\Models\Order;
use Symfony\Component\HttpFoundation\Response;

/**
 * Added to ecom's public tracking routes. Logged-in customers have no separate tracking page:
 * the form sends them to "My Orders", and a tracking link for their own order opens the order
 * in their account (which shows the full tracking).
 */
class TrackOrderForCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = efront()->customer();

        if (! $customer) {
            return $next($request);
        }

        if ($request->routeIs('ecom.track.show')) {
            $order = $request->route('order');

            return $order instanceof Order && $order->customer_id === $customer->id
                ? redirect()->route('efront.account.orders.show', $order)
                : $next($request);
        }

        return redirect()->route('efront.account.orders')->with('success', 'Your orders and their tracking are listed here.');
    }
}
