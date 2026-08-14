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
            // Один 'component'-лист на ВЕСЬ блок (сайдбар фильтров+сортировки + сетка товаров +
            // пагинация) целиком, а не repeat на карточку товара — сортировка/фильтры на реальном
            // сайте работают через AJAX, завязанный на конкретные id/имена полей формы
            // (resources/themes/base/js/shop/filters.js: #catalogFilters, #catalogFilterFields,
            // #catalogSelectedFilters, #catalogProducts, #catalogPagination), которых у донорской
            // вёрстки естественно нет и структурно не может быть — попытка сплайсить донорские
            // фильтры точечно оставила бы рабочую сетку без рабочей сортировки/фильтрации.
            // BladeExpressionMap::forType('catalog') подставляет сюда реальный
            // public.layouts.catalog_filterable_area (вынесен из catalog.blade.php целиком, со
            // своей сеткой/пагинацией внутри — donor's собственная сетка карточек тоже уезжает).
            'catalog' => [
                'leaves' => [
                    ['slug' => 'filterable_area', 'type' => 'component', 'description' => 'весь блок с сеткой товаров, сайдбаром фильтров, сортировкой и пагинацией целиком (обычно двухколоночная раскладка — сайдбар слева, товары и пагинация справа; ищи общий контейнер-обёртку ОБЕИХ колонок)'],
                ],
            ],
            // Без 'children': карточка товара — сложный интерактивный partial (корзина/избранное/
            // сравнение/quick-view, см. resources/themes/base/views/public/layouts/product.blade.php),
            // донорская вёрстка карточки структурно не может дать эти хуки через простой сплайсинг
            // текста, поэтому донорский инстанс целиком заменяется на реальный partial темы
            // (BladeExpressionMap::forType('search')['repeat_component']) — нужен только сам факт
            // повтора и его selector_path, разбирать содержимое одного инстанса незачем. У search
            // (в отличие от catalog) в РАБОЧЕЙ теме нет фильтров/сортировки вовсе — только сетка +
            // пагинация, поэтому здесь остаётся прежний repeat-механизм шага 3, без изменений.
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
            // Отзывы о товаре — репитер (текст/автор, простые поля, без хуков) как у blog, а не
            // компонентный, как у catalog — карточка отзыва не интерактивна, ей не нужен реальный
            // partial. Цена/варианты/кнопки покупки, наоборот, — 'component'-листья (см.
            // BladeExpressionMap::forType('product')): это не текстовые значения, а целые готовые
            // блоки разметки (новые public.layouts.product_price/_variations/_actions, вынесенные
            // из product.blade.php специально под это), донорская вёрстка структурно не может дать
            // им реальные корзина/избранное/сравнение/варианты-JS-хуки, тот же принцип, что и у
            // карточки товара в catalog.
            'product' => [
                'repeat' => [
                    'slug' => 'review_card',
                    'description' => 'один отзыв покупателя в списке отзывов о товаре',
                    'children' => [
                        ['slug' => 'text', 'type' => 'text', 'description' => 'текст отзыва'],
                        ['slug' => 'author', 'type' => 'text', 'description' => 'имя автора отзыва'],
                    ],
                ],
                'leaves' => [
                    ['slug' => 'image', 'type' => 'image', 'description' => 'главное фото товара'],
                    ['slug' => 'price_actions', 'type' => 'component', 'description' => 'блок с ценой и кнопками покупки (в корзину/купить сейчас/избранное/сравнить)'],
                    ['slug' => 'variations', 'type' => 'component', 'description' => 'блок выбора вариантов товара — переключатели цвета/размера/вкуса и т.п. (если на странице их нет — не включай этот слот)'],
                    ['slug' => 'description', 'type' => 'wysiwyg', 'description' => 'описание товара'],
                ],
            ],
            default => [],
        };
    }
}