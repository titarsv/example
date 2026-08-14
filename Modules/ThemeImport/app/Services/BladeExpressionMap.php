<?php

namespace Modules\ThemeImport\Services;

/**
 * Ручная карта slot -> реальное Blade-выражение, написанная один раз разработчиком по уже
 * рабочим resources/themes/base/views/public/{blog,article}.blade.php — переиспользуется на
 * каждом следующем импортируемом архиве без повторной ручной работы (см.
 * docs/dynamic-page-import-plan.md, шаг 2). Меняется, только если меняется сама вёрстка/логика
 * этих шаблонов вручную (редкое событие, синхронизировать вручную при правке base).
 *
 * kind: 'text' — {{ expr }}, 'wysiwyg' — {!! expr !!}, 'image' — conditionalImageReplacement()
 * (condition/src вместо expr).
 */
class BladeExpressionMap
{
    /**
     * @return array{
     *   repeat_source?: string, repeat_var?: string, repeat_children?: array, repeat_component?: string,
     *   after_repeat?: string, leaves?: array, breadcrumbs: string, heading: string|null,
     *   extends_params?: string, open_graph?: string
     * }|null null — тип пока не поддержан.
     */
    public function forType(string $type): ?array
    {
        return match($type){
            // Источник: resources/themes/base/views/public/blog.blade.php
            'blog' => [
                'repeat_source' => '$articles',
                'repeat_var' => 'article',
                // Донорская карточка обычно целиком обёрнута в <a href="javascript:void(0)"> —
                // repeat_link переписывает href ЭТОГО инстанса на реальную ссылку (см.
                // DynamicPageTransplanter::spliceRepeat()), иначе клик по карточке никуда не вёл
                // бы (живой баг, пойманный на реальном прогоне, не гипотеза).
                'repeat_link' => '$article->link()',
                'repeat_children' => [
                    'title' => ['kind' => 'text', 'expr' => '$article->name'],
                    'date' => ['kind' => 'text', 'expr' => "date('d.m.Y', strtotime(\$article->created_at))"],
                    'excerpt' => ['kind' => 'wysiwyg', 'expr' => '$article->excerpt'],
                    'image' => [
                        'kind' => 'image',
                        'condition' => '!empty($article->image)',
                        'src' => '$article->image->url()',
                    ],
                ],
                'after_repeat' => "@include('public.layouts.pagination', ['paginator' => \$articles])",
                'breadcrumbs' => "@if(empty(\$current_category)){!! Breadcrumbs::render('blog') !!}@else{!! Breadcrumbs::render('content_category', \$current_category) !!}@endif",
                'heading' => '{{ $seo->name }}',
            ],
            // Источник: resources/themes/base/views/public/article.blade.php
            'article' => [
                'leaves' => [
                    'date' => ['kind' => 'text', 'expr' => "date('d.m.Y', strtotime(\$article->created_at))"],
                    'body' => ['kind' => 'wysiwyg', 'expr' => 'html_entity_decode($article->body)'],
                    'image' => [
                        'kind' => 'image',
                        'condition' => '!empty($article->image)',
                        'src' => '$article->image->url()',
                    ],
                ],
                'breadcrumbs' => "{!! Breadcrumbs::render('blog_item', \$article) !!}",
                // Как и у blog/static: donor's собственный <h1> уже вырезан ArchiveParser'ом
                // (см. DynamicSlotVocabulary — почему в article больше нет слота 'title'), поэтому
                // <h1>{{ $seo->name }}</h1> вставляется тем же автоматическим путём, что и везде,
                // а не пытается найти место donor's заголовка (которого здесь физически уже нет).
                'heading' => '{{ $seo->name }}',
            ],
            // Источник: resources/themes/base/views/public/catalog.blade.php. 'filterable_area' —
            // 'component'-лист (см. DynamicSlotVocabulary): заменяет ВЕСЬ найденный узел на реальный
            // public.layouts.catalog_filterable_area (вынесен из catalog.blade.php целиком —
            // сайдбар фильтров/сортировки + сетка товаров + пагинация, полностью рабочие, включая
            // AJAX — см. её докблок в DynamicSlotVocabulary). Донорская вёрстка этой области отсюда
            // теряется целиком (не просто карточка товара, как раньше в шаге 3) — сознательный
            // компромисс: без реальных id/имён полей формы AJAX-фильтрация donor's вёрсткой в
            // принципе недостижима (см. docs/dynamic-page-import-plan.md, шаг 7).
            'catalog' => [
                'leaves' => [
                    'filterable_area' => [
                        'kind' => 'component',
                        'replacement' => "@include('public.layouts.catalog_filterable_area')",
                    ],
                ],
                'breadcrumbs' => "@if(!empty(\$additional_crumb)){!! Breadcrumbs::render('filter', \$category, \$additional_crumb) !!}@elseif(!empty(\$category)){!! Breadcrumbs::render('categories', \$category) !!}@else{!! Breadcrumbs::render('catalog') !!}@endif",
                'heading' => "{{ !empty(\$seo->getAttributes()['name']) ? \$seo->getAttributes()['name'] : \$seo->name }}",
                // public.layouts.main использует $pagination (rel=prev/next в <head>) и
                // $root_category (подсветка активного пункта в шапке) — те же extra-параметры
                // @extends, что и у рабочего catalog.blade.php.
                'extends_params' => "['pagination' => \$products, 'root_category' => !empty(\$category) ? \$category->get_root_category() : null]",
            ],
            // Источник: resources/themes/base/views/public/search.blade.php. В отличие от
            // остальных типов, у search НЕТ переменной $seo вовсе (ProductsController::search()
            // передаёт только $products/$search_text) — 'open_graph' переопределяет секцию
            // page_vars темплейта wrap(), не трогая $seo (иначе — Undefined variable).
            'search' => [
                'repeat_source' => '$products',
                'repeat_var' => 'product',
                'repeat_component' => "@include('public.layouts.product', ['product' => \$product])",
                'after_repeat' => "@include('public.layouts.pagination', ['paginator' => \$products])",
                'breadcrumbs' => "{!! Breadcrumbs::render('search') !!}",
                'heading' => 'Результаты поиска «{{ $search_text }}»',
                'open_graph' => "@include('public.layouts.microdata.open_graph', [\n     'title' => 'Поиск: '.\$search_text,\n     'description' => 'Поиск: '.\$search_text,\n     'image' => theme_asset('images/favicon.png')\n     ])",
            ],
            // Источник: resources/themes/base/views/public/product.blade.php. Цена/варианты/
            // атрибуты/видеообзоры/похожие товары/часто покупают вместе/кнопки покупки —
            // 'component'-листья (kind: 'component', 'replacement' вместо 'expr'/condition-src): не
            // текстовое значение, а готовый рабочий кусок разметки (реальные корзина/избранное/
            // сравнение/переключатели вариантов/циклы $attributes/$product->video_reviews/
            // $similar/$bought_together, специально вынесенные в отдельные partial'ы
            // public.layouts.product_{price,variations,actions,attributes,video_reviews,
            // related_slider} — донорская вёрстка структурно не может дать им эти хуки/данные, тот
            // же принцип, что у карточки товара в catalog). `product_related_slider` параметризован
            // ($items/$heading) — донорская вёрстка "похожих товаров" и "часто покупают вместе"
            // почти всегда структурно идентична (карточки + заголовок), дублировать partial не
            // было смысла. Отзывы — обычный полевой репитер (текст/автор), см.
            // DynamicSlotVocabulary — карточка отзыва не интерактивна, реальный partial ей не нужен.
            'product' => [
                'repeat_source' => '$reviews',
                'repeat_var' => 'review',
                'repeat_children' => [
                    'text' => ['kind' => 'text', 'expr' => '$review->review'],
                    'author' => ['kind' => 'text', 'expr' => '$review->author'],
                ],
                'leaves' => [
                    'image' => [
                        'kind' => 'image',
                        'condition' => '!empty($product->image)',
                        'src' => '$product->image->url()',
                    ],
                    'price_actions' => [
                        'kind' => 'component',
                        'replacement' => "@include('public.layouts.product_price')\n@include('public.layouts.product_actions')",
                    ],
                    'variations' => [
                        'kind' => 'component',
                        'replacement' => "@include('public.layouts.product_variations')",
                    ],
                    'attributes' => [
                        'kind' => 'component',
                        'replacement' => "@include('public.layouts.product_attributes')",
                    ],
                    'description' => ['kind' => 'wysiwyg', 'expr' => '$product->description'],
                    'video_reviews' => [
                        'kind' => 'component',
                        'replacement' => "@include('public.layouts.product_video_reviews')",
                    ],
                    'similar_products' => [
                        'kind' => 'component',
                        'replacement' => "@include('public.layouts.product_related_slider', ['items' => \$similar, 'heading' => 'Похожие товары'])",
                    ],
                    'bought_together' => [
                        'kind' => 'component',
                        'replacement' => "@include('public.layouts.product_related_slider', ['items' => \$bought_together, 'heading' => 'Часто покупают вместе'])",
                    ],
                ],
                'breadcrumbs' => "{!! Breadcrumbs::render('product', \$product, \$product->category) !!}",
                'heading' => '{{ $product->name }}',
                // Как и у search: не $seo->name (у продукта заголовок — $product->name, отдельно от
                // SEO-объекта), а картинка OpenGraph — фото товара, а не общий favicon (как у
                // остальных типов) — то же самое, что рабочий product.blade.php уже делает.
                'open_graph' => "@include('public.layouts.microdata.open_graph', [\n     'title' => \$seo->meta_title,\n     'description' => \$seo->meta_description,\n     'image' => !empty(\$product->image) ? \$product->image->url() : theme_asset('images/favicon.png')\n     ])",
            ],
            default => null,
        };
    }
}