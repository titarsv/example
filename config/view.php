<?php

/*
|--------------------------------------------------------------------------
| View Storage Paths
|--------------------------------------------------------------------------
|
| Most templating systems load templates from disk. Here you may specify
| an array of paths that should be checked for your views. Of course
| the usual Laravel view path has already been registered for you.
|
| The active theme's views (and the fallback theme's, if different) are
| prepended so plain view('public.catalog')-style calls resolve against
| resources/themes/{active} first, then resources/themes/{fallback}, then
| the default resources/views. See config/theme.php and docs/themes.md.
|
| This has to be computed here rather than in a ServiceProvider: at least
| one vendor package (webwizo/laravel-shortcodes) eagerly resolves and
| rebinds the `view` factory during its own register() call, which runs
| before any App\Providers\* provider — by then it's too late to change
| view.paths and have it take effect. Config files are loaded (via env())
| before any provider registers, so this is the only reliable place.
|
*/

$activeTheme = env('ACTIVE_THEME', 'base');
$fallbackTheme = 'base';
$themesPath = resource_path('themes');

$themeViewPaths = [];

if ($activeTheme !== $fallbackTheme) {
    $themeViewPaths[] = $themesPath.'/'.$activeTheme.'/views';
}

$themeViewPaths[] = $themesPath.'/'.$fallbackTheme.'/views';

return [

    'paths' => array_merge($themeViewPaths, [
        resource_path('views'),
    ]),

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
