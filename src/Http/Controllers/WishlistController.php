<?php

namespace ME\Efront\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Product;

class WishlistController extends Controller
{
    public function index(): View
    {
        return view('efront::account.wishlist', [
            'products' => efront()->products()->whereIn('id', efront()->wishlistIds())->latest()->paginate(12),
        ]);
    }

    /**
     * Add to / remove from the wishlist (heart button).
     */
    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $customer = efront()->customer();
        $added = ! $customer->wishlists()->where('product_id', $product->id)->exists();

        $added
            ? $customer->wishlists()->create(['product_id' => $product->id])
            : $customer->wishlists()->where('product_id', $product->id)->delete();

        efront()->forgetWishlist();
        $message = $added ? 'Added to your wishlist.' : 'Removed from your wishlist.';

        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json([
            'in_wishlist' => $added,
            'count' => count(efront()->wishlistIds()),
            'message' => $message,
        ]);
    }
}
