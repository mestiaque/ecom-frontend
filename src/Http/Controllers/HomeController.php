<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\View\View;
use ME\Ecom\Models\Banner;
use ME\Ecom\Models\Brand;
use ME\Ecom\Models\Campaign;
use ME\Ecom\Models\Review;

class HomeController extends Controller
{
    /**
     * Home page. Only sections switched on in Storefront Theme → Home Page are queried.
     */
    public function index(): View
    {
        $sections = efront_theme()->homeSections();
        $on = fn (string $key) => isset($sections[$key]);
        $empty = collect();
        $data = ['sections' => $sections];

        if ($on('hero') || $on('promos')) {
            $banners = Banner::with('media')->where('is_active', true)->orderBy('sort_order')->get()->groupBy('position');
            $data['sliders'] = $banners->get('slider', $empty);
            $data['promos'] = $banners->get('promo', $empty)->take(4);
        }

        if ($on('marquee') || $on('categories')) {
            $data['categories'] = efront()->menuCategories();
        }

        if ($on('featured')) {
            $limit = (int) $sections['featured']['limit'] ?: 9;
            $featured = efront()->products()->where('is_featured', true)->latest()->take($limit)->get();

            if ($featured->isEmpty()) {
                $featured = efront()->products()->latest()->take($limit)->get();
            }

            $data['featured'] = $featured;
            $data['featuredCategories'] = $featured->pluck('category')->filter()->unique('id')->values();
        }

        if ($on('campaign')) {
            $data['campaign'] = Campaign::running()->with('media')
                ->with(['products' => fn ($q) => $q->where('is_active', true)->with(['primaryImage', 'campaigns'])->take(4)])
                ->orderBy('ends_at')
                ->first();
        }

        if ($on('new_arrivals')) {
            $data['newArrivals'] = efront()->products()->latest()->take((int) $sections['new_arrivals']['limit'] ?: 8)->get();
        }

        if ($on('lists')) {
            $data['bestSellers'] = efront()->products()
                ->withSum(['orderItems as sold_count' => fn ($q) => $q->whereHas('order', fn ($o) => $o->countsAsSale())], 'quantity')
                ->orderByDesc('sold_count')
                ->take(4)
                ->get();
            $data['topRated'] = efront()->products()->orderByDesc('rating')->orderByDesc('reviews_count')->take(4)->get();
            $data['onSale'] = efront()->products()->whereNotNull('discount_price')->whereColumn('discount_price', '<', 'price')->latest()->take(4)->get();
        }

        if ($on('brands')) {
            $data['brands'] = Brand::active()->with('media')->whereHas('media', fn ($q) => $q->where('collection', 'logo'))->orderBy('name')->take(12)->get();
        }

        if ($on('testimonials')) {
            $data['testimonials'] = Review::where('is_approved', true)->where('rating', '>=', 4)->whereNotNull('comment')
                ->with('product:id,title,slug')->latest()->take(8)->get();
        }

        return view('efront::home', $data);
    }
}
