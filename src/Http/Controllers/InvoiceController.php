<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use ME\Ecom\Models\Order;
use ME\Ecom\Services\InvoiceService;
use Symfony\Component\HttpFoundation\Response;

/**
 * The customer's invoice (same design as the admin one). Signed link, so guests can open it too.
 */
class InvoiceController extends Controller
{
    public function show(Request $request, Order $order, InvoiceService $invoices): Response
    {
        if ($request->boolean('download')) {
            return $invoices->download($order);
        }

        return response($invoices->html($order, ['Download PDF' => self::url($order, download: true)]));
    }

    public static function url(Order $order, bool $download = false): string
    {
        return URL::temporarySignedRoute('efront.invoice', now()->addDays(30), ['order' => $order->order_number] + ($download ? ['download' => 1] : []));
    }
}
