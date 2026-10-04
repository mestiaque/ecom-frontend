<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\View\View;
use ME\Ecom\Models\Banner;
use ME\Ecom\Models\Brand;
use ME\Ecom\Models\Campaign;
use ME\Ecom\Models\Review;

class HomeController extends Controller
{
    public function index(): View
    {
        $banners = Banner::where('is_active', true)->orderBy('sort_order')->get()->groupBy('position');

        $featured = efront()->products()->where('is_featured', true)->latest()->take(9)->get();

        if ($featured->isEmpty()) {
            $featured = efront()->products()->latest()->take(9)->get();
        }

        $campaign = Campaign::running()
            ->with(['products' => fn ($q) => $q->where('is_active', true)->with(['primaryImage', 'campaigns'])->take(4)])
            ->orderBy('ends_at')
            ->first();

        return view('efront::home', [
            'sliders' => $banners->get('slider', collect()),
            'promos' => $banners->get('promo', collect())->take(4),
            'categories' => efront()->menuCategories(),
            'featured' => $featured,
            'featuredCategories' => $featured->pluck('category')->filter()->unique('id')->values(),
            'newArrivals' => efront()->products()->latest()->take(8)->get(),
            'bestSellers' => efront()->products()
                ->withSum(['orderItems as sold_count' => fn ($q) => $q->whereHas('order', fn ($o) => $o->countsAsSale())], 'quantity')
                ->orderByDesc('sold_count')
                ->take(4)
                ->get(),
            'topRated' => efront()->products()->orderByDesc('rating')->orderByDesc('reviews_count')->take(4)->get(),
            'onSale' => efront()->products()->whereNotNull('discount_price')->whereColumn('discount_price', '<', 'price')->latest()->take(4)->get(),
            'campaign' => $campaign,
            'brands' => Brand::active()->whereNotNull('logo')->orderBy('name')->take(12)->get(),
            'testimonials' => Review::where('is_approved', true)->where('rating', '>=', 4)->whereNotNull('comment')
                ->with('product:id,title,slug')->latest()->take(8)->get(),
        ]);
    }
}
