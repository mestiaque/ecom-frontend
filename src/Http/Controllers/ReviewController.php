<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ME\Ecom\Models\Product;

class ReviewController extends Controller
{
    /**
     * One review per customer and product; shown after an admin approves it (Reviews page).
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:2000',
        ]);

        $customer = efront()->customer();

        if ($product->reviews()->where('customer_id', $customer->id)->exists()) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        $product->reviews()->create([
            ...$data,
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'is_approved' => false,
        ]);

        return back()->with('success', 'Thank you! Your review will appear after it is approved.');
    }
}
