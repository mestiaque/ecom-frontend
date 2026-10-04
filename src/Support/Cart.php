<?php

namespace ME\Efront\Support;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use ME\Ecom\Models\Coupon;
use ME\Ecom\Models\Customer;
use ME\Ecom\Models\Product;
use ME\Ecom\Models\ProductVariant;

/**
 * Session cart. Stores only ids and quantities; prices, stock and campaign discounts are read
 * fresh from the database every time, so the cart never shows a stale price.
 */
class Cart
{
    private const KEY = 'efront.cart';

    private const COUPON_KEY = 'efront.coupon';

    private ?Collection $lines = null;

    /**
     * @return array<string, array{product_id: int, variant_id: ?int, quantity: int}>
     */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function count(): int
    {
        return (int) array_sum(array_column($this->raw(), 'quantity'));
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    public static function key(int $productId, ?int $variantId = null): string
    {
        return $productId.'-'.($variantId ?? 0);
    }

    /**
     * @throws ValidationException
     */
    public function add(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['product' => 'This product is not available.']);
        }

        if ($product->has_variants && ! $variant) {
            throw ValidationException::withMessages(['variant_id' => 'Please choose an option first.']);
        }

        if ($variant && ($variant->product_id !== $product->id || ! $variant->is_active)) {
            throw ValidationException::withMessages(['variant_id' => 'This option is not available.']);
        }

        $key = self::key($product->id, $variant?->id);
        $items = $this->raw();
        $total = ($items[$key]['quantity'] ?? 0) + max(1, $quantity);

        $this->ensureInStock($product, $variant, $total);

        $items[$key] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $total];
        $this->save($items);
    }

    /**
     * @throws ValidationException
     */
    public function update(string $key, int $quantity): void
    {
        $items = $this->raw();

        if (! isset($items[$key])) {
            return;
        }

        if ($quantity < 1) {
            $this->remove($key);

            return;
        }

        $product = Product::find($items[$key]['product_id']);
        $variant = $items[$key]['variant_id'] ? ProductVariant::find($items[$key]['variant_id']) : null;

        if (! $product) {
            $this->remove($key);

            return;
        }

        $this->ensureInStock($product, $variant, $quantity);

        $items[$key]['quantity'] = $quantity;
        $this->save($items);
    }

    public function remove(string $key): void
    {
        $items = $this->raw();
        unset($items[$key]);
        $this->save($items);
    }

    public function clear(): void
    {
        session()->forget([self::KEY, self::COUPON_KEY]);
        $this->lines = null;
    }

    /**
     * Cart lines with live product data. Missing / inactive products are dropped.
     *
     * @return Collection<int, object{key: string, product: Product, variant: ?ProductVariant, quantity: int, unit_price: float, regular_price: float, line_total: float, stock: int, image: ?string}>
     */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $items = $this->raw();
        $products = Product::active()
            ->with(['primaryImage', 'campaigns', 'variants' => fn ($q) => $q->with(['values', 'image'])])
            ->whereIn('id', array_column($items, 'product_id'))
            ->get()
            ->keyBy('id');

        $lines = collect();

        foreach ($items as $key => $item) {
            $product = $products->get($item['product_id']);
            $variant = $item['variant_id'] ? $product?->variants->firstWhere('id', $item['variant_id']) : null;

            if (! $product || ($item['variant_id'] && (! $variant || ! $variant->is_active))) {
                continue;
            }

            $unitPrice = $product->finalPrice($variant);

            $lines->push((object) [
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'quantity' => (int) $item['quantity'],
                'unit_price' => $unitPrice,
                'regular_price' => $variant ? $variant->regular_price : (float) $product->price,
                'line_total' => $unitPrice * (int) $item['quantity'],
                'stock' => (int) ($variant ? $variant->stock : $product->stock),
                'image' => $variant?->image?->thumb_url ?? $product->thumbnail,
            ]);
        }

        return $this->lines = $lines;
    }

    /**
     * Items for ShippingCalculator::quote().
     *
     * @return array<int, array{product: Product, quantity: int}>
     */
    public function shippingItems(): array
    {
        return $this->lines()->map(fn ($line) => ['product' => $line->product, 'quantity' => $line->quantity])->values()->all();
    }

    /**
     * Every product in the cart has free delivery.
     */
    public function shipsFree(): bool
    {
        return $this->lines()->isNotEmpty() && $this->lines()->every(fn ($line) => $line->product->free_delivery);
    }

    public function subtotal(): float
    {
        return (float) $this->lines()->sum('line_total');
    }

    /**
     * Lines whose quantity is more than the stock left.
     */
    public function unavailableLines(): Collection
    {
        return $this->lines()->filter(fn ($line) => $line->stock < $line->quantity);
    }

    /**
     * Items in the format OrderService::place() expects.
     *
     * @return array<int, array{product_id: int, variant_id: ?int, quantity: int}>
     */
    public function orderItems(): array
    {
        return $this->lines()->map(fn ($line) => [
            'product_id' => $line->product->id,
            'variant_id' => $line->variant?->id,
            'quantity' => $line->quantity,
        ])->values()->all();
    }

    public function couponCode(): ?string
    {
        return session(self::COUPON_KEY);
    }

    public function setCoupon(?string $code): void
    {
        $code ? session([self::COUPON_KEY => strtoupper(trim($code))]) : session()->forget(self::COUPON_KEY);
    }

    /**
     * Applied coupon and its discount for the current subtotal. A coupon that no longer applies
     * (expired, minimum not met) gives a zero discount and the reason.
     *
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function couponDiscount(?Customer $customer = null): array
    {
        $code = $this->couponCode();

        if (! $code) {
            return ['coupon' => null, 'discount' => 0.0, 'error' => null];
        }

        $subtotal = $this->subtotal();
        $coupon = Coupon::where('code', $code)->first();
        $error = $coupon ? $coupon->rejectionReason($subtotal, $customer) : 'Invalid coupon code.';

        return [
            'coupon' => $coupon,
            'discount' => $error ? 0.0 : $coupon->discountFor($subtotal),
            'error' => $error,
        ];
    }

    /**
     * @throws ValidationException
     */
    private function ensureInStock(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $stock = (int) ($variant ? $variant->stock : $product->stock);
        $max = (int) config('efront.max_quantity', 20);

        if ($stock < 1) {
            throw ValidationException::withMessages(['quantity' => "{$product->title} is out of stock."]);
        }

        if ($quantity > $stock) {
            throw ValidationException::withMessages(['quantity' => "Only {$stock} left in stock for {$product->title}."]);
        }

        if ($quantity > $max) {
            throw ValidationException::withMessages(['quantity' => "You can order at most {$max} of one item."]);
        }
    }

    /**
     * @param  array<string, array{product_id: int, variant_id: ?int, quantity: int}>  $items
     */
    private function save(array $items): void
    {
        session([self::KEY => $items]);
        $this->lines = null;
    }
}
