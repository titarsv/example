<?php

namespace Modules\ThemeImport\Services;

/**
 * Фиксированный словарь слотов на каждый динамический тип страницы — то, что ИИ ищет в разметке
 * донора (AiServiceInterface::mapDynamicPageSlots()), и для чего в BladeExpressionMap есть готовое
 * реальное Blade-выражение. Список сознательно минимальный — самое ценное визуально, не всё, что
 * теоретически можно вытащить из донора (см. docs/dynamic-page-import-plan.md, шаг 2).
 */
class DynamicSlotVocabulary
{
    /**
     * @return array{repeat?: array{slug: string, description: string, children: array}, leaves?: array}
     *         Пустой массив (без ключей repeat/leaves) — тип пока не поддержан трансплантом.
     */
    public function forType(string $type): array
    {
        return match($type){
            'blog' => [
                'repeat' => [
                    'slug' => 'post_card',
                    'description' => 'один анонс статьи в списке (карточка)',
                    'children' => [
                        ['slug' => 'image', 'type' => 'image', 'description' => 'картинка анонса'],
                        ['slug' => 'title', 'type' => 'text', 'description' => 'заголовок статьи'],
                        ['slug' => 'date', 'type' => 'text', 'description' => 'дата публикации'],
                        ['slug' => 'excerpt', 'type' => 'text', 'description' => 'краткий анонс/отрывок текста статьи'],
                    ],
                ],
            ],
            // Без 'children': карточка товара — сложный интерактивный partial (корзина/избранное/
            // сравнение/quick-view, см. resources/themes/base/views/public/layouts/product.blade.php),
            // донорская вёрстка карточки структурно не может дать эти хуки через простой сплайсинг
            // текста, поэтому донорский инстанс целиком заменяется на реальный partial темы
            // (BladeExpressionMap::forType('catalog')['repeat_component']) — нужен только сам факт
            // повтора и его selector_path, разбирать содержимое одного инстанса незачем.
            'catalog' => [
                'repeat' => [
                    'slug' => 'product_card',
                    'description' => 'один товар в сетке/списке товаров каталога',
                    'children' => [],
                ],
            ],
            // Тот же компонентный повтор, что у catalog — search.blade.php переиспользует тот же
            // partial карточки товара.
            'search' => [
                'repeat' => [
                    'slug' => 'product_card',
                    'description' => 'один товар в списке результатов поиска',
                    'children' => [],
                ],
            ],
            // Без 'title': ArchiveParser::parseHtmlFile() уже вырезает первый <h1> из контента ДО
            // того, как транспланter его увидит (см. "Находка 4" в docs/page-import-plan.md,
            // рассчитано на static-страницы) — реального donor's <h1> для article-слота "title"
            // просто не существует к этому моменту. Вместо попытки поймать его точечно — как и
            // у blog/static, синтетический <h1>{{ $seo->name }}</h1> вставляется автоматически
            // через wrap() (см. BladeExpressionMap::forType('article')['heading']).
            'article' => [
                'leaves' => [
                    ['slug' => 'image', 'type' => 'image', 'description' => 'главная картинка статьи'],
                    ['slug' => 'date', 'type' => 'text', 'description' => 'дата публикации статьи'],
                    ['slug' => 'body', 'type' => 'wysiwyg', 'description' => 'основной текст статьи целиком'],
                ],
            ],
            default => [],
        };
    }
}