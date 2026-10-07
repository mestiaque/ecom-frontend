<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\Transaction;
use ME\Ecom\Services\Payments\PaymentException;
use ME\Ecom\Services\Payments\PaymentManager;

/**
 * Online payment of a placed order: send the customer to bKash / SSLCommerz and handle their return.
 * Pay / switch links are signed (guests have no account); the callback is protected by a secret token per try.
 */
class PaymentController extends Controller
{
    public function __construct(private PaymentManager $payments) {}

    public function pay(Order $order): RedirectResponse
    {
        if (! $this->payments->canPayOnline($order)) {
            return redirect()->to(self::resultUrl($order));
        }

        try {
            $url = $this->payments->start($order, fn (Transaction $transaction, string $token) => route('efront.payment.callback', [$transaction, $token]));
        } catch (PaymentException $e) {
            return redirect()->to(self::resultUrl($order))->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /**
     * Gateway return URL (bKash: GET with ?paymentID&status, SSLCommerz: cross-site POST to /success|fail|cancel).
     * Runs without the session: a cross-site POST carries no session cookie (SameSite=Lax), and starting a new
     * session here would log the customer out. So: confirm the payment, then 303 to a GET page that has the session.
     */
    public function callback(Request $request, Transaction $transaction, string $token): RedirectResponse
    {
        abort_unless($this->payments->tokenMatches($transaction, $token), 404);

        $this->payments->complete($transaction, $request);

        return redirect()->to(route('efront.payment.done', [$transaction, $token]), 303);
    }

    /**
     * Back on the shop with the customer's session: show the outcome on the order page.
     */
    public function done(Transaction $transaction, string $token): RedirectResponse
    {
        abort_unless($this->payments->tokenMatches($transaction, $token), 404);

        $paid = $transaction->status === 'success';

        return redirect()->to(self::resultUrl($transaction->order))->with(
            $paid ? 'success' : 'error',
            $paid ? 'Payment received. Thank you!' : (str($transaction->note)->after(': ')->toString() ?: 'The payment was not completed.').' Your order is saved — you can try again.'
        );
    }

    /**
     * Give up on online payment and pay the rider in cash instead.
     */
    public function switchToCod(Order $order): RedirectResponse
    {
        $open = ! in_array($order->status->value, ['cancelled', 'returned'], true);

        if ($open && $order->paidAmount() <= 0 && $order->payment_method !== PaymentMethod::CashOnDelivery) {
            $order->forceFill(['payment_method' => PaymentMethod::CashOnDelivery])->save();
            $order->notes()->create(['note' => 'Customer switched the payment to Cash on Delivery.']);
        }

        return redirect()->to(self::resultUrl($order))->with('success', 'Done — you will pay the rider in cash on delivery.');
    }

    /**
     * Signed order result page (also used right after checkout).
     */
    public static function resultUrl(Order $order): string
    {
        return URL::temporarySignedRoute('efront.checkout.success', now()->addDay(), ['order' => $order->order_number]);
    }

    public static function payUrl(Order $order): string
    {
        return URL::temporarySignedRoute('efront.payment.pay', now()->addDay(), ['order' => $order->order_number]);
    }

    public static function codUrl(Order $order): string
    {
        return URL::temporarySignedRoute('efront.payment.cod', now()->addDay(), ['order' => $order->order_number]);
    }
}
