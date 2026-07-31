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
