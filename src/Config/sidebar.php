<?php

// Shop data (products, banners, pages …) is managed from the ecom package; the storefront only adds its theme.
return [
    [
        'title' => 'Storefront Theme',
        'icon' => 'fas fa-palette',
        'route' => 'efront.admin.theme.edit',
        'for_active' => 'efront.admin.theme',
        'icon_color' => 'icc-38',
        'permit' => 'efront_theme.edit',
        'sl' => 6,
    ],
];
