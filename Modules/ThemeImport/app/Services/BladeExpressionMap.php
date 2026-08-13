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
            // Источник: resources/themes/base/views/public/catalog.blade.php. 'repeat_component' —
            // донорский инстанс карточки товара заменяется целиком на реальный интерактивный
            // partial темы (см. DynamicSlotVocabulary — почему у catalog нет repeat_children).
            // Донорский сайдбар фильтров/сортировки — специфичный для каждого архива UI, не
            // фиксированный словарь, как у blog/article — сознательно НЕ трансплантируется в v1
            // (см. docs/dynamic-page-import-plan.md, шаг 3), остаётся статичной декоративной
            // вёрсткой донора, как и непокрытые словарём виджеты у blog/article.
            'catalog' => [
                'repeat_source' => '$products',
                'repeat_var' => 'product',
                'repeat_component' => "@include('public.layouts.product', ['product' => \$product])",
                'after_repeat' => "@include('public.layouts.pagination', ['paginator' => \$products])",
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
            default => null,
        };
    }
}