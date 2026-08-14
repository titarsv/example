<?php

namespace Modules\PageImport\Services\Concerns;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * DOM-механика, вынесенная из SchemaBuilder (см. docs/page-import-plan.md, стадия 1.3 и «Доработка
 * границ repeater'а») — поиск повторяющихся узлов по selector_path от ИИ, резолв простых
 * CSS-подобных селекторов детей, сериализация фрагментов. Все методы чистые (не зависят от
 * состояния конкретного билдера), поэтому вынесены в трейт — переиспользуются и в SchemaBuilder
 * (статические ACF-страницы), и в Modules\ThemeImport\Services\DynamicPageTransplanter
 * (blog/article и далее, см. docs/dynamic-page-import-plan.md, шаг 2) без дублирования.
 */
trait ResolvesDomNodes
{
    /**
     * Находит все инстансы повторяющегося элемента репитера в разметке по его selector_path. Два
     * поддерживаемых случая:
     *
     * 1. У самого повторяющегося элемента есть класс ("... .trading-wrapper .trading-item" —
     *    последний токен ".trading-item") — глобальный поиск по этому классу, ближайшие токены
     *    селектора (обёртки) игнорируются, класс самого повтора обычно достаточно специфичен.
     * 2. У повторяющегося элемента класса НЕТ, только голый тег ("... .about-main__wrapper div",
     *    ".about-main__wrapper > div") — по правилу 9 промпта (`BuildsPageMarkupPrompt`) это
     *    единственный способ ИИ описать повтор классических «голых» карточек без обёртки на
     *    каждый элемент. Ищем ближайший токен слева с классом — это контейнер, инстансы — его
     *    ПРЯМЫЕ дети с тегом последнего токена.
     *
     * @return DOMElement[]|null null — селектор совсем нечитаем (ни класса, ни тега), пустой
     *         массив — читаем, но в разметке ничего не нашлось.
     */
    private function findRepeaterInstanceNodes(string $selectorPath, DOMXPath $xpath): ?array{
        $parts = array_values(array_filter(
            preg_split('/\s+/', trim($selectorPath)),
            fn($part) => $part !== '' && $part !== '>'
        ));

        if(empty($parts)){
            return null;
        }

        $last = end($parts);

        if(preg_match('/\.([a-zA-Z0-9_-]+)/', $last, $m)){
            $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' {$m[1]} ')]");
            return $nodes === false ? [] : iterator_to_array($nodes);
        }

        if(count($parts) < 2 || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $last)){
            // Ни класса на инстансе, ни голого тега с классом-контейнером слева — селектор
            // непригоден ни для одного из двух поддерживаемых случаев.
            return null;
        }

        for($i = count($parts) - 2; $i >= 0; $i--){
            if(!preg_match('/\.([a-zA-Z0-9_-]+)/', $parts[$i], $m)){
                continue;
            }

            $containers = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' {$m[1]} ')]");

            if($containers === false){
                return [];
            }

            $instances = [];
            foreach($containers as $container){
                foreach($container->childNodes as $child){
                    if($child instanceof DOMElement && strtolower($child->tagName) === strtolower($last)){
                        $instances[] = $child;
                    }
                }
            }

            return $instances;
        }

        return null;
    }

    /**
     * Поднимает границу повторителя выше найденного по классу узла, если реальная повторяющаяся
     * структура на самом деле на уровень (или несколько) выше (см. план, «Доработка границ
     * repeater'а» — найденный по классу узел бывает вложен в сетку-обёртку вроде
     * class="col-lg-3 col-md-6", и именно обёртка реально повторяется).
     *
     * Поднимаемся на уровень, только если это ОДНОЗНАЧНО тот же повтор: у всех найденных узлов
     * РАЗНЫЕ непосредственные родители (сами узлы ещё не сиблинги — иначе поднимать некуда,
     * граница уже верная), и эти родители — сиблинги друг друга одинакового тега под одним общим
     * "дедом". Не совпало — останавливаемся на текущем уровне, ничего не портим.
     *
     * @param DOMElement[] $instances
     * @return DOMElement[]
     */
    private function expandToRepeatBoundary(array $instances): array{
        $maxLevels = 3;

        for($level = 0; $level < $maxLevels; $level++){
            $parents = [];
            $distinct = true;

            foreach($instances as $instance){
                $parent = $instance->parentNode;

                if(!($parent instanceof DOMElement)){
                    return $instances;
                }

                if(in_array($parent, $parents, true)){
                    // Минимум два инстанса делят родителя — они уже прямые сиблинги,
                    // это и есть настоящая граница повторителя, подниматься некуда.
                    $distinct = false;
                    break;
                }

                $parents[] = $parent;
            }

            if(!$distinct || !$this->parentsFormRepeatBoundary($parents)){
                break;
            }

            $instances = $parents;
        }

        return $instances;
    }

    /**
     * Родители образуют границу повторителя, если это сиблинги одного тега под общим родителем —
     * простая, но осознанно консервативная эвристика: ложноположительный подъём (взяли слишком
     * широкий узел) хуже, чем отказ от подъёма (остаёмся на уже рабочем, просто не идеальном
     * уровне вложенности).
     *
     * @param DOMElement[] $parents
     */
    private function parentsFormRepeatBoundary(array $parents): bool{
        if(count($parents) < 2){
            return false;
        }

        $grandParent = $parents[0]->parentNode;

        if(!($grandParent instanceof DOMElement)){
            return false;
        }

        $expectedTag = $parents[0]->tagName;

        foreach($parents as $parent){
            if($parent->parentNode !== $grandParent || $parent->tagName !== $expectedTag){
                return false;
            }
        }

        return true;
    }

    /**
     * Резолвер CSS-подобного селектора: цепочка простых токенов через пробел/">" (descendant
     * combinator), каждый токен — tag, tag.class, .class, tag:nth-of-type(N). Изначально
     * поддерживался только ОДИН токен целиком, но живой тест (proper-loud/blog.html, слот "title")
     * поймал, что модель регулярно даёт двух-трёхуровневые селекторы вида "div.info span.title"
     * несмотря на явный запрет в промпте — тот же класс проблемы, что "осиротевшие поля" (см. план).
     * Каждое звено цепочки при этом остаётся ПРОСТЫМ (та же безопасность: не самое общее совпадение
     * "весь инстанс", а конкретный узел), поэтому расширение до цепочки не ослабляет исходную
     * гарантию — просто резолвит несколько уровней вложенности вместо одного.
     * Более сложные комбинаторы/псевдоклассы (кроме nth-of-type) по-прежнему не поддерживаются —
     * в этом случае возвращаем null; вызывающий код тогда пропускает поле для этой строки, а не
     * подставляет весь инстанс целиком (иначе в текстовое поле утекут соседние узлы — иконка,
     * другие подписи; так уже ловили баг на живых данных, см. план).
     */
    private function resolveSimpleSelector(DOMXPath $xpath, DOMElement $context, string $selector): ?DOMNode{
        $selector = trim($selector);

        // Пустой селектор — единственный случай, когда "это сам инстанс" осмыслен. Совпадение
        // с тегом контекста ("div" при контексте <div class="trading-item">) НЕ считаем этим
        // случаем — почти всегда имеется в виду вложенный <div>, а не сам инстанс.
        if($selector === ''){
            return $context;
        }

        $tokens = array_values(array_filter(
            preg_split('/\s+/', $selector),
            fn($token) => $token !== '' && $token !== '>'
        ));

        if($tokens === []){
            return null;
        }

        $node = $context;

        foreach($tokens as $token){
            if(!($node instanceof DOMElement)){
                return null;
            }

            $node = $this->resolveSimpleSelectorToken($xpath, $node, $token);

            if($node === null){
                return null;
            }
        }

        return $node;
    }

    /**
     * Резолвит один токен цепочки ("tag", "tag.class", "tag.class1.class2", "tag:nth-of-type(N)")
     * относительно $context — см. resolveSimpleSelector(). Несколько классов подряд (".class1.class2")
     * — реальный, живьём встреченный паттерн модели для узла с несколькими CSS-классами
     * (donor's `class="product-page__info-buttons sticky"` → модель иногда пишет
     * "div.product-page__info-buttons.sticky") — раньше поддерживался только ОДИН класс на токен,
     * такой селектор молча проваливался целиком (весь токен не матчился регэкспом). Каждый указанный
     * класс — отдельный XPath contains()-предикат (И-семантика, как у CSS ".a.b" — узел должен
     * содержать ВСЕ перечисленные классы, порядок не важен).
     */
    private function resolveSimpleSelectorToken(DOMXPath $xpath, DOMElement $context, string $token): ?DOMNode{
        if(!preg_match('/^([a-zA-Z0-9]*)((?:\.[a-zA-Z0-9_-]+)*)(?::nth-of-type\((\d+)\))?$/', $token, $m)){
            return null;
        }

        $tag = $m[1] !== '' ? $m[1] : '*';
        $nth = isset($m[3]) ? (int)$m[3] : 1;

        preg_match_all('/\.([a-zA-Z0-9_-]+)/', $m[2] ?? '', $classMatches);

        $query = './/'.$tag;
        foreach($classMatches[1] as $class){
            $query .= "[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
        }

        $matches = $xpath->query($query, $context);

        if($matches === false || $matches->length === 0){
            return null;
        }

        return $matches->item(min($nth, $matches->length) - 1);
    }

    /**
     * Заменяет donor's <img> на условную конструкцию: если реальное значение доступно, свапает
     * только src (сохраняя остальные атрибуты донора — класс, loading, alt), иначе оставляет
     * оригинальный тег как есть. Общий механизм для SchemaBuilder (oembed-поле, значение —
     * id записи медиатеки) и Modules\ThemeImport\Services\DynamicPageTransplanter (реальный
     * accessor модели, например $article->image) — отличается только $condition/$srcExpression.
     *
     * @param string $condition PHP/Blade-выражение для @if(...) — истинно, если реальное
     *        изображение доступно
     * @param string $srcExpression Blade-выражение, дающее URL (без кавычек и {{ }})
     */
    private function conditionalImageReplacement(string $imgTag, string $condition, string $srcExpression): string{
        $dynamicTag = preg_replace('/src=(["\']).*?\1/', 'src="{{ '.$srcExpression.' }}"', $imgTag, 1);

        return "@if({$condition}){$dynamicTag}@else{$imgTag}@endif";
    }

    private function replaceFirst(string $haystack, string $needle, string $replacement): string{
        if($needle === ''){
            return $haystack;
        }

        $pos = strpos($haystack, $needle);

        if($pos === false){
            return $haystack;
        }

        return substr_replace($haystack, $replacement, $pos, strlen($needle));
    }

    private function parseFragment(string $html): DOMDocument{
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="__root__">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $dom;
    }

    private function innerHtml(DOMNode $node): string{
        $html = '';
        foreach($node->childNodes as $child){
            $html .= $node->ownerDocument->saveHTML($child);
        }
        return $html;
    }

    private function outerHtml(DOMNode $node): string{
        return $node->ownerDocument->saveHTML($node);
    }
}