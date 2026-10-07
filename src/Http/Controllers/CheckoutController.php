<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\ShippingZone;
use ME\Ecom\Services\OrderService;
use ME\Ecom\Services\Payments\PaymentManager;
use ME\Ecom\Services\ShippingCalculator;
use ME\Efront\Models\Address;
use ME\Efront\Support\Cart;

/**
 * Checkout: shipping address (saved or new), billing address (same as shipping, saved or new),
 * delivery area and payment. Logged-in customers can save new addresses to their address book.
 */
class CheckoutController extends Controller
{
    public function __construct(private Cart $cart, private PaymentManager $payments) {}

    public function index(ShippingCalculator $shipping): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('efront.cart')->with('error', 'Your cart is empty.');
        }

        $customer = efront()->customer();
        $subtotal = $this->cart->subtotal();
        $addresses = $customer
            ? $customer->addresses()->with(['division', 'district', 'upazila'])->get()
            : collect();

        return view('efront::checkout.index', [
            'customer' => $customer,
            'addresses' => $addresses,
            'shippingAddresses' => $addresses->where('type', 'shipping')->values(),
            'billingAddresses' => $addresses->where('type', 'billing')->values(),
            'lines' => $this->cart->lines(),
            'unavailable' => $this->cart->unavailableLines(),
            'subtotal' => $subtotal,
            'coupon' => $this->cart->couponDiscount($customer),
            'zones' => ShippingZone::active()->get()->each(fn (ShippingZone $zone) => $zone->setAttribute('quote', $shipping->quote($zone, $this->cart->shippingItems(), $subtotal))),
            'paymentMethods' => efront()->paymentMethods(),
            // Methods paid on the gateway's page (bKash, SSLCommerz) => sandbox?
            'onlineMethods' => collect(efront()->paymentMethods())->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $this->payments->gateway($method)?->isSandbox()])
                ->reject(fn ($sandbox) => $sandbox === null)->all(),
        ]);
    }

    public function store(Request $request, OrderService $orders): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('efront.cart')->with('error', 'Your cart is empty.');
        }

        $methods = array_map(fn (PaymentMethod $method) => $method->value, efront()->paymentMethods());

        $data = $request->validate([
            'customer_email' => 'nullable|email|max:150',
            'shipping_zone_id' => ['required', Rule::exists('ecom_shipping_zones', 'id')->where('is_active', true)],
            'payment_method' => ['required', Rule::in($methods)],
            'transaction_id' => 'nullable|string|max:60',
            'customer_note' => 'nullable|string|max:1000',
        ], [
            'shipping_zone_id.required' => 'Choose a delivery area.',
        ]);

        if ($this->cart->unavailableLines()->isNotEmpty()) {
            return redirect()->route('efront.cart')->with('error', 'Some items no longer have enough stock. Please update your cart.');
        }

        $shipping = $this->resolveAddress($request, 'shipping');
        $billing = $request->boolean('billing_same', true) ? null : $this->resolveAddress($request, 'billing');
        $customer = efront()->customer();

        $note = trim(implode("\n", array_filter([
            $data['customer_note'] ?? null,
            ! empty($data['transaction_id']) ? PaymentMethod::from($data['payment_method'])->label().' transaction ID: '.$data['transaction_id'] : null,
        ])));

        $order = $orders->place([
            'customer_id' => $customer?->id,
            'customer_name' => $shipping->name,
            'customer_phone' => $shipping->phone,
            'customer_email' => $data['customer_email'] ?? $customer?->email,
            'shipping_address' => $shipping->street,
            'city' => $shipping->area,
            'billing_address' => $billing ? "{$billing->name}, {$billing->phone}, {$billing->full}" : null,
            'shipping_zone_id' => (int) $data['shipping_zone_id'],
            'payment_method' => $data['payment_method'],
            'coupon_code' => $this->cart->couponCode(),
            'customer_note' => $note ?: null,
        ], $this->cart->orderItems());

        $this->cart->clear();
        $this->notifyCustomer($order);

        // bKash / card: pay on the gateway now; the order stays "unpaid" until the gateway confirms
        if ($this->payments->canPayOnline($order)) {
            return redirect()->to(PaymentController::payUrl($order));
        }

        return redirect()->to(PaymentController::resultUrl($order));
    }

    public function success(Order $order): View
    {
        return view('efront::checkout.success', [
            'order' => $order->load(['items', 'transactions' => fn ($q) => $q->latest('id')]),
            'canPayOnline' => $this->payments->canPayOnline($order),
        ]);
    }

    /**
     * A saved address of the logged-in customer ("{type}_address_id"), or the typed "{type}[...]"
     * fields — saved to the address book when "save_{type}" is ticked.
     *
     * @throws ValidationException
     */
    private function resolveAddress(Request $request, string $type): Address
    {
        $customer = efront()->customer();
        $savedId = $request->input("{$type}_address_id");

        if ($customer && $savedId && $savedId !== 'new') {
            $address = $customer->addresses()->with(['division', 'district', 'upazila'])->find($savedId);

            if (! $address) {
                throw ValidationException::withMessages(["{$type}_address_id" => 'Choose one of your saved addresses.']);
            }

            return $address;
        }

        $data = $request->validate(Address::rules("{$type}."), Address::messages("{$type}."))[$type];
        $address = Address::fromInput([...$data, 'type' => $type]);

        if (! $address->hasValidArea()) {
            throw ValidationException::withMessages(["{$type}.upazila_id" => 'The upazila does not belong to the chosen district.']);
        }

        if ($customer && $request->boolean("save_{$type}")) {
            $customer->saveAddress($address);
        }

        return $address;
    }

    private function notifyCustomer(Order $order): void
    {
        $message = "Thank you! Your order {$order->order_number} (".ecom_money($order->total, true).') has been placed. Track: '.$order->trackingPageUrl();

        me_sms($order->customer_phone, $message);

        if ($order->customer_email) {
            me_mail($order->customer_email, 'Order '.$order->order_number.' placed', '<p>Hi '.e($order->customer_name).',</p><p>'.e($message).'</p>');
        }
    }
}
