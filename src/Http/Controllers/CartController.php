<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\Ecom\Models\Product;
use ME\Ecom\Models\ProductVariant;
use ME\Efront\Support\Cart;

/**
 * Cart page and AJAX cart actions. Every action answers JSON (count + refreshed cart / mini-cart HTML)
 * to AJAX calls and redirects back for normal form posts.
 */
class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index(): View
    {
        return view('efront::cart.index', $this->summary());
    }

    public function mini(): View
    {
        return view('efront::partials.mini-cart', ['cart' => $this->cart]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1|max:'.config('efront.max_quantity', 20),
            'buy_now' => 'nullable|boolean',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = ! empty($data['variant_id']) ? ProductVariant::find($data['variant_id']) : null;

        $this->cart->add($product, $variant, (int) ($data['quantity'] ?? 1));

        if ($request->boolean('buy_now')) {
            return $request->expectsJson()
                ? response()->json(['redirect' => route('efront.checkout')])
                : redirect()->route('efront.checkout');
        }

        return $this->respond($request, "{$product->title} added to cart.");
    }

    public function update(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['quantity' => 'required|integer|min:0|max:'.config('efront.max_quantity', 20)]);

        $this->cart->update($key, (int) $data['quantity']);

        return $this->respond($request, 'Cart updated.');
    }

    public function destroy(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $this->cart->remove($key);

        return $this->respond($request, 'Item removed from cart.');
    }

    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['coupon_code' => 'required|string|max:50']);

        $this->cart->setCoupon($data['coupon_code']);
        $error = $this->cart->couponDiscount(efront()->customer())['error'];

        if ($error) {
            $this->cart->setCoupon(null);

            throw ValidationException::withMessages(['coupon_code' => $error]);
        }

        return $this->respond($request, 'Coupon applied.');
    }

    public function removeCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart->setCoupon(null);

        return $this->respond($request, 'Coupon removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(): array
    {
        $subtotal = $this->cart->subtotal();
        $freeMin = efront()->freeShippingMin();

        return [
            'cart' => $this->cart,
            'lines' => $this->cart->lines(),
            'subtotal' => $subtotal,
            'coupon' => $this->cart->couponDiscount(efront()->customer()),
            'freeShippingMin' => $this->cart->shipsFree() ? null : $freeMin,
            'shipsFree' => $this->cart->shipsFree(),
            'freeShippingLeft' => $freeMin !== null ? max(0, $freeMin - $subtotal) : null,
        ];
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json([
            'message' => $message,
            'count' => $this->cart->count(),
            'mini' => view('efront::partials.mini-cart', ['cart' => $this->cart])->render(),
            'cart' => view('efront::cart.partials.content', $this->summary())->render(),
        ]);
    }
}
