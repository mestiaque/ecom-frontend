<?php

use ME\Efront\Support\Storefront;
use ME\Efront\Support\Theme;

if (! function_exists('efront')) {
    /**
     * Storefront helper: logged-in customer, store info, menu categories, wishlist, cart count.
     */
    function efront(): Storefront
    {
        return app(Storefront::class);
    }
}

if (! function_exists('efront_theme')) {
    /**
     * Storefront theme settings (admin → Storefront Theme): colours, fonts, buttons, home sections.
     */
    function efront_theme(): Theme
    {
        return app(Theme::class);
    }
}

if (! function_exists('efront_asset')) {
    /**
     * asset() with ?v=<file modified time>, so browsers load a CSS/JS file again after it changes.
     */
    function efront_asset(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
