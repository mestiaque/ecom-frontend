<?php

/*
| Storefront theme defaults. Admin → Storefront Theme saves changes in the `settings` table
| (key "efront_theme"); anything not saved there falls back to these values.
| Colours are #RRGGBB. Section "bg" / "heading" null = theme default.
*/
return [
    // glass = frosted-glass cards over a glowing background, classic = solid white theme
    'style' => env('EFRONT_THEME', 'glass'),

    'fonts' => [
        'heading' => 'Plus Jakarta Sans',
        'body' => 'Inter',
        'label_style' => 'caps', // caps = small uppercase labels, script = handwritten (Dancing Script)
    ],

    'shape' => [
        'card_radius' => 18,   // px
        'button_radius' => 50, // px (50 = pill)
    ],

    'colors' => [
        'primary' => '#e8281a',
        'secondary' => '#f6a623',
        'heading' => '#1a1a1a',
        'text' => '#555555',
        'price' => '#e8281a',
        'label' => '#e8281a',          // small label above section titles
        'category_label' => '#f6a623', // category name on product cards
    ],

    // Product card badges: discount (-12% / campaign deal), new arrival, hot (featured)
    'badges' => [
        'sale_bg' => '#e8281a',
        'sale_text' => '#ffffff',
        'new_bg' => '#16a34a',
        'new_text' => '#ffffff',
        'hot_bg' => '#f6a623',
        'hot_text' => '#1a1a1a',
    ],

    // Glass style only: page background gradient and the five glowing orbs
    'glass' => [
        'bg_1' => '#fff4ee',
        'bg_2' => '#fdf0f6',
        'bg_3' => '#f2efff',
        'bg_4' => '#eaf8fc',
        'orb_1' => '#ff3d2e',
        'orb_2' => '#8b5cf6',
        'orb_3' => '#ffb020',
        'orb_4' => '#22d3ee',
        'orb_5' => '#ec4899',
        'glow' => 75, // orb strength 0-100
    ],

    'header' => [
        'topbar' => true,
        'topbar_bg' => '#111111',
        'topbar_text' => '#bbbbbb',
        'navbar_bg' => '#ffffff',
        'navbar_text' => '#1a1a1a',
        'pagehead_bg' => '#2d0000',
        'pagehead_text' => '#ffffff',
    ],

    'footer' => [
        'bg' => '#1a1a1a',
        'text' => '#999999',
        'heading' => '#ffffff',
    ],

    // label / icon null = the button shows no text / no icon
    'buttons' => [
        'primary' => ['label' => null, 'icon' => null, 'bg' => '#e8281a', 'text' => '#ffffff'],
        'add_to_cart' => ['label' => 'Add to Cart', 'icon' => 'fas fa-shopping-cart', 'bg' => '#e8281a', 'text' => '#ffffff'],
        'buy_now' => ['label' => 'Buy Now', 'icon' => 'fas fa-bolt', 'bg' => '#f6a623', 'text' => '#1a1a1a'],
        'quick_add' => ['label' => null, 'icon' => 'fas fa-plus', 'bg' => '#e8281a', 'text' => '#ffffff'],
        'checkout' => ['label' => 'Proceed to Checkout', 'icon' => 'fas fa-lock', 'bg' => '#e8281a', 'text' => '#ffffff'],
        'place_order' => ['label' => 'Place Order', 'icon' => 'fas fa-check-circle', 'bg' => '#e8281a', 'text' => '#ffffff'],
    ],

    'icons' => [
        'search' => 'fas fa-search',
        'wishlist' => 'far fa-heart',
        'account' => 'far fa-user',
        'cart' => 'fas fa-shopping-bag',
        'menu' => 'fas fa-bars',
    ],

    'card' => [
        'image_fit' => 'contain', // contain = whole photo visible, cover = fill the box (may crop)
        'image_height' => 215,    // px
        'image_padding' => 6,     // px
        'image_bg' => '#ffffff',
        'show_category' => true,
        'show_description' => true,
        'show_rating' => true,
    ],

    // Home page sections, in display order
    'sections' => [
        'hero' => [
            'enabled' => true,
            'badge' => 'Trusted online shopping',
            'title' => 'Everything you love,',
            'highlight' => 'delivered fast',
            'text' => 'Genuine products, cash on delivery and easy returns — shop thousands of items from trusted brands.',
            'button' => 'Shop Now',
            'bg' => null,
            'heading' => null,
        ],
        'marquee' => ['enabled' => true, 'bg' => null, 'heading' => null],
        'features' => [
            'enabled' => true,
            'bg' => null,
            'heading' => null,
            'items' => [
                ['icon' => 'fas fa-shipping-fast', 'title' => 'Fast Delivery', 'text' => ''],
                ['icon' => 'fas fa-hand-holding-usd', 'title' => 'Cash on Delivery', 'text' => 'Pay when you receive'],
                ['icon' => 'fas fa-undo', 'title' => 'Easy Returns', 'text' => 'Hassle-free return policy'],
                ['icon' => 'fas fa-headset', 'title' => 'Support', 'text' => ''],
            ],
        ],
        'categories' => [
            'enabled' => true,
            'label' => 'What We Offer',
            'title' => 'Browse by',
            'highlight' => 'Category',
            'text' => 'Find exactly what you need from our hand-picked collections.',
            'limit' => 12,
            'bg' => null,
            'heading' => null,
        ],
        'featured' => [
            'enabled' => true,
            'label' => 'Hand Picked',
            'title' => 'Featured',
            'highlight' => 'Products',
            'text' => '',
            'limit' => 9,
            'filter' => true,
            'button' => 'View All Products',
            'bg' => null,
            'heading' => null,
        ],
        'campaign' => ['enabled' => true, 'label' => 'Limited Time Offer', 'button' => 'Grab the Deal', 'bg' => null, 'heading' => null],
        'promos' => [
            'enabled' => true,
            'label' => 'Special Offers',
            'title' => 'Deals &',
            'highlight' => 'Offers',
            'text' => '',
            'bg' => null,
            'heading' => null,
        ],
        'new_arrivals' => [
            'enabled' => true,
            'label' => 'Just In',
            'title' => 'New',
            'highlight' => 'Arrivals',
            'text' => '',
            'limit' => 8,
            'bg' => null,
            'heading' => null,
        ],
        'lists' => ['enabled' => true, 'bg' => null, 'heading' => null],
        'brands' => ['enabled' => true, 'bg' => null, 'heading' => null],
        'testimonials' => [
            'enabled' => true,
            'label' => 'What People Say',
            'title' => 'Our Customers',
            'highlight' => 'Feedback',
            'text' => '',
            'bg' => null,
            'heading' => null,
        ],
        'newsletter' => [
            'enabled' => true,
            'label' => 'Join Us',
            'title' => 'Create an account & shop',
            'highlight' => 'faster',
            'text' => 'Save your address, follow your orders and keep a wishlist of things you love.',
            'bg' => null,
            'heading' => null,
        ],
    ],
];
