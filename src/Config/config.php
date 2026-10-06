<?php

return [
    'name' => 'efront',

    // Storefront URL prefix ("" = site root, e.g. "shop" puts the store at /shop)
    'route_prefix' => env('EFRONT_ROUTE_PREFIX', ''),

    // Products per page on shop / category / campaign pages
    'per_page' => 12,

    // Products created within this many days get the "New" badge
    'new_badge_days' => 14,

    // Most products a customer can put in the cart for one line
    'max_quantity' => 20,

    // Print banner title / subtitle / button over slider images. Off by default because most
    // banners are designed images that already contain their text.
    'banner_text' => env('EFRONT_BANNER_TEXT', false),

    /*
    | One-time codes sent while registering. The account is created only after every enabled
    | channel is verified. In the local environment a code that could not be sent (SMS / mail
    | not configured) is written to the log instead, so registration can still be tested.
    */
    'registration_otp' => [
        'phone' => env('EFRONT_OTP_PHONE', true),
        'email' => env('EFRONT_OTP_EMAIL', true),
        'expires' => 10,        // minutes
        'resend_after' => 60,   // seconds
        'max_attempts' => 5,
    ],

    // Customer profile photo (KB) — stored on the "public" disk
    'avatar_max_kb' => 2048,

    // Picture of the text hero shown when there is no "slider" banner (Website → Banners).
    // The hero texts are edited in Admin → Storefront Theme → Home Page → Hero; only "image" is used here.
    'hero' => [
        'badge' => 'Trusted online shopping',
        'title' => 'Everything you love,',
        'highlight' => 'delivered fast',
        'text' => 'Genuine products, cash on delivery and easy returns — shop thousands of items from trusted brands.',
        'image' => 'efront/img/banner-img.jpg',
    ],
];
