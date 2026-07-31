<?php

if(!function_exists('base_url')){
    function base_url(string $path): string {
        return App\Helpers\Helper::baseUrl($path);
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

if(!function_exists('module_active')){
    /**
     * Whether an optional store feature is enabled. Works uniformly for real
     * nwidart/laravel-modules packages (blog, reviews, wishlist, coupons, ai,
     * notifications — same slug as the module name in lowercase) and for
     * in-core-only toggles that never became a physical Modules/ package
     * (cart_checkout). Both read the same `modules_settings` row via
     * SettingsActivator, so admin's "Модули" toggle and this helper never
     * disagree. Unknown slugs default to enabled.
     */
    function module_active(string $slug): bool {
        return App\Support\Modules\SettingsActivator::isActive($slug);
    }
}
