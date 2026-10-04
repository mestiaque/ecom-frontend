<?php

use ME\Efront\Support\Storefront;

if (! function_exists('efront')) {
    /**
     * Storefront helper: logged-in customer, store info, menu categories, wishlist, cart count.
     */
    function efront(): Storefront
    {
        return app(Storefront::class);
    }
}
