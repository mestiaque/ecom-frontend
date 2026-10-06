<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\Ecom\Models\Brand;
use ME\Ecom\Models\Campaign;
use ME\Ecom\Models\Category;
use ME\Ecom\Models\Product;

/**
 * Product listings: shop, category, brand, campaign and search. Filters come from the query string
 * (?q=, ?category=, ?brands[]=, ?min_price=, ?max_price=, ?in_stock=1, ?sort=); AJAX requests get
 * only the results block so the filter sidebar can update the grid without a page reload.
 */
class ShopController extends Controller
{
    public const SORTS = [
        'latest' => 'Newest first',
        'popular' => 'Best selling',
        'rating' => 'Top rated',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'name' => 'Name: A to Z',
    ];

    public function index(Request $request): View
    {
        $category = $request->filled('category')
            ? Category::active()->where('slug', $request->input('category'))->first()
            : null;

        return $this->listing($request, [
            'title' => $request->filled('q') ? 'Search results for "'.$request->input('q').'"' : ($category->name ?? 'Shop'),
            'category' => $category,
            'scope' => $category ? fn (Builder $q) => $q->whereIn('category_id', [$category->id, ...$category->descendantIds()]) : null,
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request, [
            'title' => $category->name,
            'description' => $category->description,
            'banner' => $category->banner_url,
            'category' => $category,
            'scope' => fn (Builder $q) => $q->whereIn('category_id', [$category->id, ...$category->descendantIds()]),
        ]);
    }

    public function brand(Request $request, Brand $brand): View
    {
        abort_unless($brand->is_active, 404);

        return $this->listing($request, [
            'title' => $brand->name,
            'description' => $brand->description,
            'brand' => $brand,
            'scope' => fn (Builder $q) => $q->where('brand_id', $brand->id),
        ]);
    }

    public function campaign(Request $request, Campaign $campaign): View
    {
        abort_unless($campaign->is_active, 404);

        return $this->listing($request, [
            'title' => $campaign->title,
            'description' => $campaign->description,
            'banner' => $campaign->banner_url,
            'campaign' => $campaign,
            'scope' => fn (Builder $q) => $q->whereHas('campaigns', fn ($c) => $c->whereKey($campaign->id)),
        ]);
    }

    /**
     * Live search for the search overlay.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['html' => '', 'count' => 0]);
        }

        $query = efront()->products()->where(fn ($q) => $this->search($q, $term));
        $count = (clone $query)->count();

        return response()->json([
            'count' => $count,
            'url' => route('efront.shop', ['q' => $term]),
            'html' => view('efront::shop.partials.suggestions', [
                'products' => $query->take(6)->get(),
                'count' => $count,
                'term' => $term,
            ])->render(),
        ]);
    }

    /**
     * @param  array{title: string, scope: ?callable, category?: ?Category, brand?: Brand, campaign?: Campaign, description?: ?string, banner?: ?string}  $context
     */
    private function listing(Request $request, array $context): View
    {
        $price = DB::raw('CAST(COALESCE(discount_price, price) AS DECIMAL(12,2))');
        $query = efront()->products()
            ->when($context['scope'], $context['scope'])
            ->when(trim((string) $request->input('q')), fn ($q, $term) => $q->where(fn ($q) => $this->search($q, $term)))
            ->when(array_filter((array) $request->input('brands')), fn ($q, $brands) => $q->whereIn('brand_id', $brands))
            ->when(is_numeric($request->input('min_price')), fn ($q) => $q->where($price, '>=', (float) $request->input('min_price')))
            ->when(is_numeric($request->input('max_price')), fn ($q) => $q->where($price, '<=', (float) $request->input('max_price')))
            ->when($request->boolean('in_stock'), fn ($q) => $q->where('stock', '>', 0));

        $sort = array_key_exists($request->input('sort'), self::SORTS) ? $request->input('sort') : 'latest';

        match ($sort) {
            'popular' => $query->withSum(['orderItems as sold_count' => fn ($q) => $q->whereHas('order', fn ($o) => $o->countsAsSale())], 'quantity')
                ->orderByDesc('sold_count'),
            'rating' => $query->orderByDesc('rating')->orderByDesc('reviews_count'),
            'price_asc' => $query->orderBy($price),
            'price_desc' => $query->orderByDesc($price),
            'name' => $query->orderBy('title'),
            default => $query->latest(),
        };

        $data = [
            ...$context,
            'products' => $query->orderByDesc('id')->paginate((int) config('efront.per_page', 12))->withQueryString(),
            'sorts' => self::SORTS,
            'sort' => $sort,
        ];

        if ($request->ajax()) {
            return view('efront::shop.partials.results', $data);
        }

        $priceRange = Product::active()->selectRaw('MIN(COALESCE(discount_price, price)) as min, MAX(COALESCE(discount_price, price)) as max')->first();

        return view('efront::shop.index', [
            ...$data,
            'categories' => efront()->menuCategories(),
            'brands' => Brand::active()
                ->whereHas('products', fn ($q) => $q->where('is_active', true))
                ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('name')
                ->get(),
            'priceMin' => (int) floor((float) $priceRange?->min),
            'priceMax' => (int) ceil((float) $priceRange?->max),
        ]);
    }

    private function search(Builder $query, string $term): Builder
    {
        return $query->where('title', 'like', "%{$term}%")
            ->orWhere('sku', 'like', "%{$term}%")
            ->orWhere('short_description', 'like', "%{$term}%")
            ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$term}%"));
    }
}
