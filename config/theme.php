<?php

/*
|--------------------------------------------------------------------------
| Storefront theme
|--------------------------------------------------------------------------
|
| The storefront (resources/views/public/*, plus its scss/js/images/fonts)
| lives under resources/themes/{theme}. `active` is the theme actually
| served; `fallback` is where view/asset lookups land when the active
| theme doesn't override a given file. Set `active` to anything other than
| `fallback` to run a project-specific theme that only overrides what it
| needs, while everything else is inherited from the base theme.
|
| See docs/themes.md for how to create and switch themes.
|
*/

return [
    'active' => env('ACTIVE_THEME', 'base'),

    'fallback' => 'base',

    'path' => resource_path('themes'),

    'available' => [
        'base',
        'custom',
    ],
];
