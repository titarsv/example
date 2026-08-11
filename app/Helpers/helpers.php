<?php

if(!function_exists('base_url')){
    function base_url(string $path): string {
        return App\Helpers\Helper::baseUrl($path);
    }
}

if(!function_exists('url_path')){
    /**
     * Как base_url(), но без подмены на javascript:void(0), когда $path
     * совпадает с текущей страницей — та проверка не учитывает query-string,
     * а значит ломает любые ссылки-табы на одной странице с разными
     * параметрами (сравнение по категориям и т.п.), где такая ссылка
     * обязана оставаться кликабельной.
     */
    function url_path(string $path): string {
        return env('APP_URL').(app()->getLocale() != config()->get('app.locale') ? '/'.app()->getLocale().$path : $path);
    }
}

if(!function_exists('translit')){
    function translit(string $path): string {
        return App\Helpers\Helper::translit($path);
    }
}

if(!function_exists('is_bot')){
    function is_bot(string $path): bool {
        return App\Helpers\Helper::isBot($path);
    }
}

if(!function_exists('localizationFields')){
    function localizationFields(array $fields): array {
        $localized = [];
        $languages = config('app.available_locales', ['en']);

        foreach ($fields as $field) {
            foreach ($languages as $language) {
                $localized[] = $field . '_' . $language;
            }
        }

        return $localized;
    }
}

if(!function_exists('field')){
    /**
     * Автобиндинг значения ACF-подобного поля страницы/блока по slug (или по
     * пути через точку для вложенных повторителей: 'items.0.title'), без
     * ручной вставки Blade-кода из кнопки "Generate" в админке шаблонов и без
     * warning-ов, если поле отсутствует в уже сохранённых данных (переименовано
     * или удалено в схеме шаблона).
     *
     * @param array|object $source $fields шаблона, либо переменная цикла повторителя
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    function field($source, string $path, $default = ''){
        return App\Helpers\Fields::value($source, $path, $default);
    }
}

if(!function_exists('theme_path')){
    /**
     * Filesystem path into the active theme's source directory
     * (resources/themes/{active}), or another theme's if $theme is given.
     */
    function theme_path(string $path = '', ?string $theme = null): string {
        $theme = $theme ?? config('theme.active');

        return rtrim(config('theme.path').'/'.$theme.($path !== '' ? '/'.$path : ''), '/');
    }
}

if(!function_exists('theme_relative_path')){
    /**
     * Active theme's source path relative to base_path() — for APIs that
     * want a disk-relative path rather than an absolute one, e.g. the
     * 'local' filesystem disk (rooted at base_path(), see
     * config/filesystems.php) used by the admin page/block template editor
     * (PagesController, BlocksController) to read/write theme blade files.
     */
    function theme_relative_path(string $path = '', ?string $theme = null): string {
        $theme = $theme ?? config('theme.active');

        return 'resources/themes/'.$theme.($path !== '' ? '/'.$path : '');
    }
}

if(!function_exists('theme_mix')){
    /**
     * Versioned URL for a compiled theme asset (css/js built by webpack.mix.js
     * into public/themes/{active}/...), via Laravel Mix's manifest lookup.
     */
    function theme_mix(string $path): \Illuminate\Support\HtmlString|string {
        return mix('themes/'.config('theme.active').'/'.ltrim($path, '/'));
    }
}

if(!function_exists('theme_asset')){
    /**
     * URL for a static theme asset (image/font copied as-is into
     * public/themes/{active}/... — not run through Mix versioning).
     */
    function theme_asset(string $path): string {
        return asset('themes/'.config('theme.active').'/'.ltrim($path, '/'));
    }
}

if(!function_exists('module_active')){
    /**
     * Whether an optional store feature is enabled. Works uniformly for real
     * nwidart/laravel-modules packages (blog, reviews, wishlist, coupons, ai,
     * notifications — same slug as the module name in lowercase) and for
     * in-core-only toggles that never became a physical Modules/ package
     * (cart_checkout). Both read config/modules_settings.php via
     * SettingsActivator — edit that file (or the matching MODULE_* env var)
     * to toggle a module, then `php artisan config:clear`. Unknown slugs
     * default to enabled.
     */
    function module_active(string $slug): bool {
        return App\Support\Modules\SettingsActivator::isActive($slug);
    }
}
