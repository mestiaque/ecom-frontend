<?php

namespace ME\Efront\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Models\Category;
use ME\Ecom\Models\Page;
use ME\Ecom\Models\Product;
use ME\Ecom\Services\ShippingCalculator;
use ME\Efront\Models\Customer;

/**
 * Per-request storefront data used by the layout, product cards and controllers (via efront()).
 */
class Storefront
{
    private ?Collection $menuCategories = null;

    private ?Collection $footerPages = null;

    /** @var array{value: ?float}|null */
    private ?array $freeShippingMin = null;

    /** @var array<int, int>|null */
    private ?array $wishlistIds = null;

    public function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer && ! $customer->is_blocked ? $customer : null;
    }

    /**
     * "Track Order" link: the customer's own orders when logged in, else the public tracking form.
     */
    public function trackUrl(): string
    {
        return $this->customer() ? route('efront.account.orders') : route('ecom.track.form');
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return ecom_setting($key, $default);
    }

    public function storeName(): string
    {
        return (string) ecom_setting('store_name', config('app.name'));
    }

    public function logoUrl(): ?string
    {
        return get_image('ecom_store_logo'); // image setting in me_media (metheme)
    }

    /**
     * Active product query with everything a product card needs (image, category, campaign price, rating).
     *
     * @return Builder<Product>
     */
    public function products(): Builder
    {
        return Product::active()
            ->with(['primaryImage', 'category', 'campaigns'])
            ->withAvg(['reviews as rating' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)]);
    }

    /**
     * Top-level active categories with their active children (navbar, footer, search box).
     */
    public function menuCategories(): Collection
    {
        $activeProducts = fn ($q) => $q->where('is_active', true);

        // products_count includes the products of active subcategories
        return $this->menuCategories ??= Category::active()
            ->whereNull('parent_id')
            ->with(['media', 'children' => fn ($q) => $q->where('is_active', true)->with('media')->withCount(['products' => $activeProducts])])
            ->withCount(['products' => $activeProducts])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each(fn (Category $category) => $category->products_count += $category->children->sum('products_count'));
    }

    public function footerPages(): Collection
    {
        return $this->footerPages ??= Page::where('is_active', true)->orderBy('title')->get(['title', 'slug']);
    }

    /**
     * @return array<int, int>
     */
    public function wishlistIds(): array
    {
        return $this->wishlistIds ??= $this->customer()?->wishlists()->pluck('product_id')->all() ?? [];
    }

    public function inWishlist(int $productId): bool
    {
        return in_array($productId, $this->wishlistIds(), true);
    }

    public function forgetWishlist(): void
    {
        $this->wishlistIds = null;
    }

    public function cartCount(): int
    {
        return app(Cart::class)->count();
    }

    /**
     * Lowest order amount that ships free in every zone (Shipping → Delivery Discounts), else null.
     */
    public function freeShippingMin(): ?float
    {
        $this->freeShippingMin ??= ['value' => app(ShippingCalculator::class)->freeDeliveryMin()];

        return $this->freeShippingMin['value'];
    }

    /**
     * Payment methods enabled on the Payment Methods page (cash on delivery when nothing is saved yet).
     *
     * @return array<int, PaymentMethod>
     */
    public function paymentMethods(): array
    {
        $enabled = array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $method) => ecom_setting("payment_{$method->value}_enabled") === '1'
        ));

        return $enabled ?: [PaymentMethod::CashOnDelivery];
    }

    /**
     * Social links from Store Info settings.
     *
     * @return array<string, string> icon class => url
     */
    public function socialLinks(): array
    {
        $whatsapp = preg_replace('/\D/', '', (string) ecom_setting('social_whatsapp'));

        return array_filter([
            'fab fa-facebook-f' => ecom_setting('social_facebook'),
            'fab fa-instagram' => ecom_setting('social_instagram'),
            'fab fa-youtube' => ecom_setting('social_youtube'),
            'fab fa-tiktok' => ecom_setting('social_tiktok'),
            'fab fa-whatsapp' => $whatsapp ? 'https://wa.me/'.$whatsapp : null,
        ]);
    }
}
