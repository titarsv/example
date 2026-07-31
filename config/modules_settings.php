<?php

/*
|--------------------------------------------------------------------------
| Module toggles
|--------------------------------------------------------------------------
|
| Enables/disables the optional store modules extracted into Modules/*
| (plus the in-core-only cart_checkout flag). Edit this file directly per
| environment, or override any entry via .env without touching the file.
| After changing a value, clear the config cache if one is in use:
|   php artisan config:clear   (or config:cache to rebuild it)
|
*/

return [
    'blog' => env('MODULE_BLOG', true),
    'reviews' => env('MODULE_REVIEWS', true),
    'wishlist' => env('MODULE_WISHLIST', true),
    'coupons' => env('MODULE_COUPONS', true),
    'notifications' => env('MODULE_NOTIFICATIONS', true),
    'ai' => env('MODULE_AI', true),
    'cart_checkout' => env('MODULE_CART_CHECKOUT', true),
];
