<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use ME\Ecom\Models\AttributeValue;
use ME\Ecom\Models\Product;
use ME\Ecom\Models\ProductVariant;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $data = $this->productData($product);
        $reviews = $product->reviews()->where('is_approved', true)->latest()->get();
        $customer = efront()->customer();

        return view('efront::product.show', [
            ...$data,
            'reviews' => $reviews,
            'ratingBreakdown' => collect(range(5, 1))->mapWithKeys(fn ($star) => [$star => $reviews->where('rating', $star)->count()]),
            'canReview' => $customer && ! $product->reviews()->where('customer_id', $customer->id)->exists(),
            'breadcrumbs' => $this->breadcrumbs($product),
            'related' => efront()->products()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->inRandomOrder()
                ->take(4)
                ->get(),
        ]);
    }

    /**
     * Product popup (quick view) loaded by AJAX from product cards.
     */
    public function quickView(Product $product): View
    {
        return view('efront::product.quick-view', $this->productData($product));
    }

    /**
     * @return array<string, mixed>
     */
    private function productData(Product $product): array
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'images', 'category.parent', 'brand', 'warranty', 'campaigns',
            'variants' => fn ($q) => $q->where('is_active', true)->with(['values', 'image']),
        ]);
        $product->loadAvg(['reviews as rating' => fn ($q) => $q->where('is_approved', true)], 'rating');
        $product->loadCount(['reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)]);

        return [
            'product' => $product,
            'campaign' => $product->activeCampaign(),
            'attributes' => $this->variantAttributes($product->variants),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'values' => $variant->values->pluck('id')->all(),
                'price' => $product->finalPrice($variant),
                'regular' => $variant->regular_price,
                'stock' => $variant->stock,
                'sku' => $variant->sku,
                'image' => $variant->image?->url,
            ])->values(),
        ];
    }

    /**
     * Attributes used by the variants with their values, e.g. Color: [Red, Blue], Size: [M, L].
     *
     * @param  Collection<int, ProductVariant>  $variants
     * @return Collection<int, array{name: string, type: string, values: Collection<int, AttributeValue>}>
     */
    private function variantAttributes(Collection $variants): Collection
    {
        return $variants->flatMap(fn (ProductVariant $variant) => $variant->values)
            ->unique('id')
            ->groupBy('attribute_id')
            ->map(fn (Collection $values) => [
                'name' => $values->first()->attribute->name,
                'type' => $values->first()->attribute->type,
                'sort' => [$values->first()->attribute->sort_order, $values->first()->attribute_id],
                'values' => $values->sortBy('sort_order')->values(),
            ])
            ->sortBy('sort')
            ->values();
    }

    /**
     * @return array<int, array{label: string, url: ?string}>
     */
    private function breadcrumbs(Product $product): array
    {
        $crumbs = [];
        $category = $product->category;

        while ($category) {
            array_unshift($crumbs, ['label' => $category->name, 'url' => route('efront.category', $category)]);
            $category = $category->parent;
        }

        return [['label' => 'Shop', 'url' => route('efront.shop')], ...$crumbs, ['label' => $product->title, 'url' => null]];
    }
}
