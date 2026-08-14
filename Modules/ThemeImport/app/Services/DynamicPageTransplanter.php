<?php

namespace Modules\ThemeImport\Services;

use DOMXPath;
use Modules\Ai\Services\AiServiceInterface;
use Modules\PageImport\Services\ArchiveParser;
use Modules\PageImport\Services\Concerns\ResolvesDomNodes;

/**
 * Транспланter для blog/article (и далее — catalog/product/search по мере расширения
 * DynamicSlotVocabulary/BladeExpressionMap, см. docs/dynamic-page-import-plan.md, шаги 2–3).
 * В отличие от SchemaBuilder (который сплайсит @field('slug') — значение поля ACF-схемы),
 * сплайсит РЕАЛЬНОЕ Blade-выражение из BladeExpressionMap на месте найденного ИИ слота, и
 * оборачивает результат в тот же каркас, что и рабочие
 * resources/themes/base/views/public/{blog,article}.blade.php. DOM-механика (поиск инстансов
 * repeater'а, резолв простых селекторов, сериализация) переиспользована из ResolvesDomNodes —
 * та же, что уже использует SchemaBuilder для статических ACF-страниц.
 */
class DynamicPageTransplanter
{
    use ResolvesDomNodes;

    private ArchiveParser $parser;
    private AiServiceInterface $ai;
    private DynamicSlotVocabulary $vocabulary;
    private BladeExpressionMap $expressionMap;

    public function __construct(){
        $this->parser = new ArchiveParser();
        $this->ai = app(AiServiceInterface::class);
        $this->vocabulary = new DynamicSlotVocabulary();
        $this->expressionMap = new BladeExpressionMap();
    }

    /**
     * @return string|null Полностью собранный Blade-файл (готов к записи в
     *         views/public/{blog|article}.blade.php новой темы), либо null — тип не поддержан,
     *         в разметке не нашлось контентной области, или ИИ/сплайсинг не дали ни одного слота.
     */
    public function build(string $htmlFilePath, string $pageType): ?string{
        $map = $this->expressionMap->forType($pageType);

        if($map === null){
            return null;
        }

        $vocab = $this->vocabulary->forType($pageType);
        $result = $this->parser->parseHtmlFile($htmlFilePath);

        if($result === null){
            return null;
        }

        $prep = $this->parser->preprocessForAi($result['content'], $result['svgs']);
        $slots = $this->ai->mapDynamicPageSlots($prep['content'], $vocab);

        if($slots === null){
            return null;
        }

        $content = $prep['content'];
        $splicedAnything = false;

        if(!empty($vocab['repeat']) && !empty($slots['repeat'])){
            [$content, $spliced] = $this->spliceRepeat($content, $slots['repeat'], $map);
            $splicedAnything = $splicedAnything || $spliced;
        }

        if(!empty($vocab['leaves']) && !empty($slots['leaves'])){
            [$content, $spliced] = $this->spliceLeaves($content, $slots['leaves'], $map['leaves'] ?? []);
            $splicedAnything = $splicedAnything || $spliced;
        }

        if(!$splicedAnything){
            // Ничего реально не сплайсилось — приходить с пустым транспланtом хуже, чем не
            // приходить вовсе (пустая тема без единой живой переменной), пусть страница остаётся
            // необработанной (тот же принцип, что и в SchemaBuilder::processRepeater()).
            return null;
        }

        $content = $this->parser->restorePlaceholders($content, $prep['placeholders']);

        // css-слаг — имя ИМЕННО ЭТОГО донорского файла (например "city" для city.html), не
        // $pageType ("catalog") — у каждого донорского файла своя изолированная per-page SCSS-
        // папка (AssetPlacer::placeStylesheetsForPage() вызывается с $page['name'], тем же именем,
        // что pathinfo() даёт и здесь). Важно при "последний обработанный побеждает" (несколько
        // донорских файлов одного типа, см. docs/dynamic-page-import-plan.md, шаг 3) — CSS должен
        // совпадать с тем же файлом, чей контент реально попал в этот Blade.
        $cssSlug = pathinfo($htmlFilePath, PATHINFO_FILENAME);

        return $this->wrap($content, $map, $cssSlug);
    }

    /**
     * @return array{0: string, 1: bool} [новый $content, был ли реально сплайсен хотя бы один слот]
     */
    private function spliceRepeat(string $content, array $repeatSlot, array $map): array{
        $dom = $this->parseFragment($content);
        $xpath = new DOMXPath($dom);

        $matches = $this->findRepeaterInstanceNodes($repeatSlot['selector_path'] ?? '', $xpath);

        if($matches === null || count($matches) === 0){
            return [$content, false];
        }

        $instances = $this->expandToRepeatBoundary($matches);
        $firstInstance = $instances[0];

        if(!empty($map['repeat_component'])){
            [$loopBody, $spliced] = $this->spliceRepeatComponent($firstInstance, $map['repeat_component']);
        }else{
            $children = is_array($repeatSlot['children'] ?? null) ? $repeatSlot['children'] : [];
            [$loopBody, $spliced] = $this->spliceRepeatChildren($firstInstance, $xpath, $children, $map);
        }

        if(!$spliced){
            // Ни один дочерний слот (или компонент) не сплайсился — репитер бесполезен (та же
            // логика, что SchemaBuilder::processRepeater() применяет к обычным ACF-репитерам):
            // лучше оставить разметку донора как есть, чем размножить статичный текст первого
            // инстанса на N строк.
            return [$content, false];
        }

        // Живой баг: карточка-repeater часто целиком обёрнута в <a> (donor's "вся карточка
        // кликабельна"), но donor's собственный href — заглушка ("javascript:void(0)"), а слот на
        // него не заводится (это не текстовое/картиночное поле). Без этого блог/статьи собирались
        // бы полностью рабочими ВНУТРИ карточки, но нажатие на саму карточку никуда не вело.
        if(!empty($map['repeat_link']) && strtolower($firstInstance->nodeName) === 'a'){
            $loopBody = preg_replace('/href=(["\']).*?\1/', "href=\"{{ {$map['repeat_link']} }}\"", $loopBody, 1);
        }

        $instanceHtmls = array_map(fn($i) => $this->outerHtml($i), $instances);
        $foreachBlock = "@foreach({$map['repeat_source']} as \${$map['repeat_var']})\n{$loopBody}\n@endforeach";

        $content = $this->replaceFirst($content, $instanceHtmls[0], $foreachBlock);

        for($i = 1; $i < count($instanceHtmls); $i++){
            $content = $this->replaceFirst($content, $instanceHtmls[$i], '');
        }

        if(!empty($map['after_repeat'])){
            $content .= "\n".$map['after_repeat']."\n";
        }

        return [$content, true];
    }

    /**
     * Компонентный повтор (catalog/search, см. BladeExpressionMap): вместо сплайсинга отдельных
     * полей донорская карточка заменяется ЦЕЛИКОМ на реальный интерактивный partial темы — донор's
     * собственная обёртка (класс/grid-атрибуты) сохраняется, заменяется только её СОДЕРЖИМОЕ, тот
     * же принцип, что products_list.blade.php оборачивает `@include('public.layouts.product', ...)`
     * в свой собственный `<div class="col">`.
     *
     * @return array{0: string, 1: bool} [тело инстанса (с заменённым содержимым), сплайсено ли]
     */
    private function spliceRepeatComponent(\DOMElement $firstInstance, string $component): array{
        $outer = $this->outerHtml($firstInstance);
        $inner = $this->innerHtml($firstInstance);

        if($inner === ''){
            // Пустой инстанс (например донорская карточка — самозакрывающийся тег без детей) —
            // заменить содержимое некуда, оставляем донора как есть.
            return [$outer, false];
        }

        $newOuter = $this->replaceFirst($outer, $inner, $component);

        return [$newOuter, $newOuter !== $outer];
    }

    /**
     * Полевой повтор (blog, см. BladeExpressionMap) — прежнее поведение: каждый дочерний слот
     * сплайсится по отдельности внутри сохранённой донорской структуры инстанса.
     *
     * @return array{0: string, 1: bool} [тело инстанса (с заменёнными полями), сплайсено ли хотя бы одно]
     */
    private function spliceRepeatChildren(\DOMElement $firstInstance, DOMXPath $xpath, array $children, array $map): array{
        $loopBody = $this->outerHtml($firstInstance);
        $spliced = false;

        foreach($children as $child){
            $slug = $child['slug'] ?? null;
            $childMap = $slug !== null ? ($map['repeat_children'][$slug] ?? null) : null;

            if($childMap === null){
                continue;
            }

            if($childMap['kind'] === 'image'){
                $imgNodes = $xpath->query('.//img', $firstInstance);

                if($imgNodes === false || $imgNodes->length === 0){
                    continue;
                }

                $imgTag = $this->outerHtml($imgNodes->item(0));
                $replacement = $this->conditionalImageReplacement($imgTag, $childMap['condition'], $childMap['src']);
                $newLoopBody = $this->replaceFirst($loopBody, $imgTag, $replacement);

                if($newLoopBody !== $loopBody){
                    $loopBody = $newLoopBody;
                    $spliced = true;
                }

                continue;
            }

            $selector = (string)($child['selector_path'] ?? '');
            $target = $this->resolveSimpleSelector($xpath, $firstInstance, $selector);

            if($target === null){
                continue;
            }

            $matchedText = $childMap['kind'] === 'wysiwyg' ? $this->innerHtml($target) : trim($target->textContent);

            if($matchedText === ''){
                continue;
            }

            $bladeExpr = $this->bladeExpression($childMap);
            $newLoopBody = $this->replaceFirst($loopBody, $matchedText, $bladeExpr);

            if($newLoopBody !== $loopBody){
                $loopBody = $newLoopBody;
                $spliced = true;
            }
        }

        return [$loopBody, $spliced];
    }

    /**
     * Все типы листьев (text/wysiwyg/image) резолвятся через selector_path от корня фрагмента —
     * НЕ через точное совпадение транскрибированного моделью текста ("value"). Так надёжнее по
     * двум независимым причинам, обе пойманы живьём на proper-loud/article.html:
     * 1. Длинный wysiwyg-текст модель может слегка переформатировать (пробелы/отступы) — точное
     *    строковое совпадение срывается, слот молча не сплайсится.
     * 2. Короткий текст (например заголовок) может ДОСЛОВНО повторяться в другом месте страницы
     *    (виджет "похожие статьи" на article.html показывает ту же статью — с чуть иной
     *    пунктуацией донора, из-за которой транскрипция модели точнее совпала с ЧУЖИМ повтором,
     *    чем с реальным <h1>) — первое найденное по strpos() совпадение попадало не туда.
     * Резолв по selector_path targeting КОНКРЕТНЫЙ узел (например именно "h1", а не любой текст,
     * похожий на заголовок) снимает обе проблемы разом: значение для сплайса берётся из САМОГО
     * резолвленного узла (гарантированно точно совпадает при обратном поиске в $content), а не
     * из независимой транскрипции модели.
     *
     * @return array{0: string, 1: bool} [новый $content, был ли реально сплайсен хотя бы один слот]
     */
    private function spliceLeaves(string $content, array $leafSlots, array $leafMap): array{
        $dom = $this->parseFragment($content);
        $xpath = new DOMXPath($dom);
        $root = $xpath->query('//*[@id="__root__"]')->item(0);

        if(!($root instanceof \DOMElement)){
            return [$content, false];
        }

        $spliced = false;

        foreach($leafSlots as $leaf){
            $slug = $leaf['slug'] ?? null;
            $childMap = $slug !== null ? ($leafMap[$slug] ?? null) : null;
            $selector = (string)($leaf['selector_path'] ?? '');

            if($childMap === null || $selector === ''){
                continue;
            }

            $target = $this->resolveSimpleSelector($xpath, $root, $selector);

            if($target === null){
                continue;
            }

            if($childMap['kind'] === 'image'){
                $imgNode = strtolower($target->nodeName) === 'img' ? $target : $xpath->query('.//img', $target)->item(0);

                if($imgNode === null){
                    continue;
                }

                $imgTag = $this->outerHtml($imgNode);
                $replacement = $this->conditionalImageReplacement($imgTag, $childMap['condition'], $childMap['src']);
                $newContent = $this->replaceFirst($content, $imgTag, $replacement);

                if($newContent !== $content){
                    $content = $newContent;
                    $spliced = true;
                }

                continue;
            }

            if($childMap['kind'] === 'component'){
                // Как и repeat_component (см. spliceRepeatComponent()) — не значение донора, а
                // готовый рабочий кусок разметки темы (например product_price/_variations/_actions,
                // см. BladeExpressionMap::forType('product')), заменяет СОДЕРЖИМОЕ найденного узла
                // целиком, донорскую обёртку (класс/атрибуты) сохраняет.
                $inner = $this->innerHtml($target);

                if($inner === ''){
                    continue;
                }

                $newContent = $this->replaceFirst($content, $inner, $childMap['replacement']);

                if($newContent !== $content){
                    $content = $newContent;
                    $spliced = true;
                }

                continue;
            }

            // innerHtml, не outerHtml — заменяем только СОДЕРЖИМОЕ найденного узла, сохраняя его
            // собственный тег/класс (донорскую вёрстку/стили), тот же принцип, что и у детей
            // repeater'а (см. SchemaBuilder::resolveChildValue()).
            $matchedHtml = $childMap['kind'] === 'wysiwyg' ? $this->innerHtml($target) : trim($target->textContent);

            if($matchedHtml === ''){
                continue;
            }

            $bladeExpr = $this->bladeExpression($childMap);
            $newContent = $this->replaceFirst($content, $matchedHtml, $bladeExpr);

            if($newContent !== $content){
                $content = $newContent;
                $spliced = true;
            }
        }

        return [$content, $spliced];
    }

    private function bladeExpression(array $childMap): string{
        return $childMap['kind'] === 'wysiwyg'
            ? "{!! {$childMap['expr']} !!}"
            : "{{ {$childMap['expr']} }}";
    }

    /**
     * Оборачивает сплайсенный контент в тот же каркас, которым написаны рабочие
     * resources/themes/base/views/public/{blog,article,catalog,search}.blade.php — layout сайта,
     * OpenGraph, крошки и (не для всех типов) <h1>. Nowdoc (не heredoc) для статичных частей — их
     * `$` не должны интерполироваться ЗДЕСЬ, это PHP-строка, которая станет Blade-исходником, а не
     * значение прямо сейчас (та же предосторожность, что в PageBuilder::wrapContent()).
     * 'extends_params'/'open_graph' — точечные переопределения под конкретный тип (см.
     * BladeExpressionMap): catalog передаёт доп. параметры в @extends (pagination/root_category),
     * search вообще не имеет переменной $seo, поэтому переопределяет секцию page_vars целиком.
     * $cssSlug — theme_mix_if_exists() (не голый theme_mix() — не у каждого донора есть SCSS
     * конкретно на эту страницу), см. PageBuilder::wrapContent() — тот же живой баг (SCSS страницы
     * компилировался, но никуда не подключался), тот же фикс.
     */
    private function wrap(string $body, array $map, string $cssSlug): string{
        $breadcrumbs = $map['breadcrumbs'];
        $heading = !empty($map['heading']) ? "        <h1 class=\"h3 mb-4\">{$map['heading']}</h1>\n" : '';
        $extendsExtra = !empty($map['extends_params']) ? ', '.$map['extends_params'] : '';
        $openGraph = $map['open_graph'] ?? $this->defaultOpenGraph();

        $header = "@extends('public.layouts.main'{$extendsExtra})\n";
        $header .= "@section('page_vars')\n    {$openGraph}\n";
        $header .= "    @if(\$__pageCss = theme_mix_if_exists('css/imported/{$cssSlug}.css'))\n";
        $header .= "        <link rel=\"stylesheet\" href=\"{{ \$__pageCss }}\">\n";
        $header .= "    @endif\n";
        $header .= "@endsection\n";
        $header .= "\n@section('content')\n    <div class=\"container py-4\">\n";
        $header .= "        <div class=\"mb-3\">{$breadcrumbs}</div>\n".$heading;

        $footer = <<<'BLADE'

    </div>
@endsection
BLADE;

        return $header.$body.$footer;
    }

    private function defaultOpenGraph(): string{
        return <<<'BLADE'
@include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => theme_asset('images/favicon.png')
     ])
BLADE;
    }
}