<?php

namespace Modules\PageImport\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Конвертирует ответ AiServiceInterface::analyzePageMarkup() в формат схемы шаблона (тот же
 * JSON, что пишет ручной редактор схемы в adminUpdateTemplateFieldsAction) и одновременно
 * расставляет @field('slug')/@foreach в Blade-контенте на месте исходных узлов — остальная
 * разметка донора (классы, структура тегов) остаётся как есть. См. план, стадия 1.3.
 */
class SchemaBuilder
{
    /** @var array<string, string> Плейсхолдер => оригинальное значение, из ArchiveParser::preprocessForAi() */
    private array $placeholders;

    /** @var array<string, int> "url картинки в медиатеке" => id записи File, из AssetImporter::importImages() */
    private array $imageIdByUrl;

    /** @var array<string, int> Использованные slug'и на текущем уровне вложенности — для защиты от коллизий */
    private array $usedSlugs = [];

    /**
     * @param array $aiFields Результат AiServiceInterface::analyzePageMarkup()
     * @param string $content HTML-контент С плейсхолдерами (тот же, что отправляли в ИИ — уже
     *                        с переписанными на медиатеку src картинок из AssetImporter::rewriteImageSrcs())
     * @param array<string, string> $placeholders Карта из ArchiveParser::preprocessForAi()
     * @param array<string, array{url: string, id: int}> $imageMap Карта из AssetImporter::importImages()
     * @return array{fields: array, blade: string, values: array}
     */
    public function build(array $aiFields, string $content, array $placeholders, array $imageMap): array{
        $this->placeholders = $placeholders;
        $this->imageIdByUrl = [];
        foreach($imageMap as $mapped){
            $this->imageIdByUrl[$mapped['url']] = $mapped['id'];
        }
        $this->usedSlugs = [];

        $schema = [];
        $values = [];

        // Курсор двигается вперёд по мере разбора полей — ИИ в целом отдаёт узлы в порядке
        // чтения документа, и поиск "от последней найденной позиции" страхует от того, что
        // короткое совпадающее значение (например "Nachricht") найдётся не в том месте разметки,
        // где стоит ДРУГОЙ, не связанный с полем, текст с тем же словом.
        $cursor = 0;

        foreach($aiFields as $node){
            if(!is_array($node) || empty($node['type']) || empty($node['slug'])){
                continue;
            }

            $slug = $this->uniqueSlug($node['slug']);

            if($node['type'] === 'repeater'){
                [$fieldSchema, $rows, $content] = $this->processRepeater($node, $slug, $content);

                if($fieldSchema === null){
                    continue;
                }

                $schema[] = $fieldSchema;
                $values[$slug] = $rows;
                // Repeater схлопывает произвольный кусок разметки — курсор после него ненадёжен,
                // начинаем поиск следующих полей заново от начала контента.
                $cursor = 0;
                continue;
            }

            $value = (string)($node['value'] ?? '');

            if($value === ''){
                continue;
            }

            $pos = strpos($content, $value, $cursor);
            if($pos === false){
                // Не нашли начиная с курсора (порядок полей от ИИ не идеален) — пробуем от
                // самого начала, но курсор в этом случае не двигаем.
                $pos = strpos($content, $value);
            }

            if($pos === false){
                continue;
            }

            if($node['type'] === 'image'){
                [$content, $newCursor] = $this->spliceImageFieldAt($content, $value, $pos, $slug, '$fields');
            }else{
                [$content, $newCursor] = $this->spliceAt($content, $pos, strlen($value), "@field('{$slug}')");
            }
            $cursor = $newCursor;

            $schema[] = $this->schemaEntry($slug, $node['type'], $node['label'] ?? $slug);
            $values[$slug] = $this->resolveLeafValue($node['type'], $value);
        }

        // Всё, что осталось не разобрано по полям (декоративные иконки/стили, которые ИИ не счёл
        // самостоятельным контентом) — возвращаем как было в исходной разметке донора.
        $content = $this->placeholderParser()->restorePlaceholders($content, $this->placeholders);

        return ['fields' => $schema, 'blade' => $content, 'values' => $values];
    }

    /**
     * Обрабатывает repeater-узел: находит ВСЕ повторяющиеся инстансы в разметке (не только тот
     * один пример, что вернула модель), строит @foreach-блок на месте первого инстанса и убирает
     * остальные, собирает значения по каждому инстансу отдельно.
     *
     * @return array{0: object|null, 1: array, 2: string} [схема поля, значения (массив строк), новый $content]
     */
    private function processRepeater(array $node, string $slug, string $content): array{
        $dom = $this->parseFragment($content);
        $xpath = new DOMXPath($dom);

        $matches = $this->findRepeaterInstanceNodes($node['selector_path'] ?? '', $xpath);

        if($matches === null || count($matches) === 0){
            return [null, [], $content];
        }

        $instances = $this->expandToRepeatBoundary($matches);

        $children = is_array($node['children'] ?? null) ? $node['children'] : [];

        // Слаги детей фиксируем один раз — используются и при сборе значений по каждому
        // инстансу, и при сборке тела @foreach-цикла, должны совпадать между собой.
        $childSlugs = [];
        $usedChildSlugs = [];
        foreach($children as $i => $child){
            $childSlugs[$i] = !empty($child['slug']) && !empty($child['type'])
                ? $this->uniqueSlugIn($usedChildSlugs, $child['slug'])
                : null;
        }

        $instanceHtmls = [];
        $rows = [];
        $firstInstance = null;

        foreach($instances as $instance){
            /** @var DOMElement $instance */
            $firstInstance ??= $instance;
            $instanceHtmls[] = $this->outerHtml($instance);

            $row = [];
            foreach($children as $i => $child){
                if($childSlugs[$i] === null){
                    continue;
                }
                $row[$childSlugs[$i]] = $this->resolveChildValue($child, $instance, $xpath);
            }
            $rows[] = $row;
        }

        // Тело цикла строим на базе ПЕРВОГО инстанса — заменяем в его копии узлы-дети на
        // field($item, 'slug'), тем же принципом, что и для верхнего уровня, но источник для
        // Blade — переменная цикла $item, а не глобальный $fields.
        $loopBody = $instanceHtmls[0];
        $childSchema = [];

        foreach($children as $i => $child){
            $childSlug = $childSlugs[$i];

            if($childSlug === null){
                continue;
            }

            // Поле попадает в схему/админку, ТОЛЬКО если реально удалось вписать field($item, ...)
            // в тело @foreach — иначе получаем "осиротевшее" поле: редактируется в админке, но
            // никак не влияет на рендер (найдено живым тестом на архиве proper-loud, см. план,
            // «Проверка на втором доноре», находка 1 — resolveChildValue()/resolveSimpleSelector()
            // не гарантируют, что селектор от ИИ однозначно резолвится на КАЖДОЙ странице).
            $spliced = false;

            if($child['type'] === 'icon'){
                // Плейсхолдер конкретно ЭТОГО (первого) инстанса — не обязательно совпадает
                // с value из ответа ИИ, там мог быть символический "__ICON_N__".
                if(preg_match('/__ICON_\d+__/', $loopBody, $m)){
                    $loopBody = $this->replaceFirst($loopBody, $m[0], "{!! field(\$item, '{$childSlug}') !!}");
                    $spliced = true;
                }
            }elseif($child['type'] === 'image'){
                $imgNodes = $xpath->query('.//img', $firstInstance);
                if($imgNodes !== false && $imgNodes->length > 0){
                    $imgTag = $this->outerHtml($imgNodes->item(0));
                    $before = $loopBody;
                    $loopBody = $this->spliceImageField($loopBody, $imgTag, $childSlug, '$item');
                    $spliced = $loopBody !== $before;
                }
            }else{
                $childValue = $rows[0][$childSlug] ?? '';
                if($childValue !== '' && str_contains($loopBody, $childValue)){
                    $loopBody = $this->replaceFirst($loopBody, $childValue, "{!! field(\$item, '{$childSlug}') !!}");
                    $spliced = true;
                }
            }

            if($spliced){
                $childSchema[] = $this->schemaEntry($childSlug, $child['type'], $child['label'] ?? $childSlug);
            }
        }

        if(empty($childSchema)){
            // Ни один дочерний узел не разметился (ИИ не дал children вовсе, или ни один не
            // сплайсился) — репитер без единого редактируемого поля бесполезен и хуже, чем
            // отсутствие репитера: даёт в админке пустую карточку добавления строки и рискует
            // размножить статичный текст первого инстанса на N визуальных копий при рендере, если
            // строки всё же появятся. Ведём себя как при "инстансы не найдены" — контент остаётся
            // как есть в исходной, нетронутой статичной разметке.
            return [null, [], $content];
        }

        $loopBody = $this->placeholderParser()->restorePlaceholders($loopBody, $this->placeholders);

        $foreachBlock = "@foreach(\$fields['{$slug}'] as \$item)\n{$loopBody}\n@endforeach";

        // Первый инстанс меняем на весь @foreach-блок, остальные — просто вырезаем (их место
        // в разметке уже отыграно циклом).
        $content = $this->replaceFirst($content, $instanceHtmls[0], $foreachBlock);
        for($i = 1; $i < count($instanceHtmls); $i++){
            $content = $this->replaceFirst($content, $instanceHtmls[$i], '');
        }

        $fieldSchema = $this->schemaEntry($slug, 'repeater', $node['label'] ?? $slug);
        $fieldSchema->fields = $childSchema;

        return [$fieldSchema, $rows, $content];
    }

    /**
     * Поднимает границу повторителя выше найденного по классу узла, если реальная повторяющаяся
     * структура на самом деле на уровень (или несколько) выше. Пример живого бага (см. план,
     * «Заметки по ходу реализации», этап 1.3): найденный класс "trading-item" лежит внутри
     * сетки-обёртки class="col-lg-3 col-md-6" — именно обёртка и есть то, что на самом деле
     * повторяется (даёт 4-колоночную раскладку). Без подъёма @foreach схлопывал бы только
     * внутренние карточки, а обёртки вокруг них оставались вне цикла и пустели.
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
     * Заменяет <img> на условную конструкцию: если для oembed-поля уже есть отхайдрейченное
     * значение ({id, image}, см. HasCustomFields), рендерим через него (->url()), иначе
     * оставляем оригинальный тег донора как есть — тот же фолбэк-паттерн, что уже используется
     * в существующих шаблонах темы (например resources/.../blocks/faq.blade.php).
     *
     * @param string $baseVar '$fields' (верхний уровень) или '$item' (внутри @foreach)
     */
    private function spliceImageField(string $content, string $imgTag, string $slug, string $baseVar): string{
        if(!str_contains($content, $imgTag)){
            return $content;
        }

        return $this->replaceFirst($content, $imgTag, $this->imageFieldReplacement($imgTag, $slug, $baseVar));
    }

    /**
     * Позиционный вариант spliceImageField() — заменяет по конкретному смещению (а не "первое
     * попавшееся" вхождение), для верхнего уровня, где важно не промахнуться мимо совпадения
     * в другом, не связанном с полем, месте разметки. См. курсор в build().
     *
     * @return array{0: string, 1: int} [новый $content, позиция сразу после вставленной замены]
     */
    private function spliceImageFieldAt(string $content, string $imgTag, int $pos, string $slug, string $baseVar): array{
        return $this->spliceAt($content, $pos, strlen($imgTag), $this->imageFieldReplacement($imgTag, $slug, $baseVar));
    }

    private function imageFieldReplacement(string $imgTag, string $slug, string $baseVar): string{
        $accessor = "field({$baseVar}, '{$slug}.image')";
        $dynamicTag = preg_replace('/src=(["\']).*?\1/', 'src="{{ '.$accessor.'->url() }}"', $imgTag, 1);

        return "@if(!empty({$accessor})){$dynamicTag}@else{$imgTag}@endif";
    }

    /**
     * @return array{0: string, 1: int} [новый $content, позиция сразу после вставленной замены]
     */
    private function spliceAt(string $content, int $pos, int $len, string $replacement): array{
        $newContent = substr_replace($content, $replacement, $pos, $len);

        return [$newContent, $pos + strlen($replacement)];
    }

    /**
     * Значение одного поля внутри одного инстанса повторителя — резолвится заново из разметки
     * ЭТОГО конкретного инстанса, а не переиспользует пример из ответа ИИ (иначе у всех строк
     * повторителя было бы одно и то же значение).
     */
    private function resolveChildValue(array $child, DOMElement $instance, DOMXPath $xpath): string{
        $type = $child['type'] ?? 'text';

        if($type === 'icon'){
            $html = $this->outerHtml($instance);
            return preg_match('/__ICON_\d+__/', $html, $m) ? ($this->placeholders[$m[0]] ?? '') : '';
        }

        if($type === 'image'){
            $imgNodes = $xpath->query('.//img', $instance);
            if($imgNodes === false || $imgNodes->length === 0){
                return '';
            }
            return $this->resolveOembedValue($this->outerHtml($imgNodes->item(0)));
        }

        $selector = (string)($child['selector_path'] ?? '');
        $target = $this->resolveSimpleSelector($xpath, $instance, $selector);

        if($target === null){
            // Не смогли надёжно найти именно этот под-узел — лучше пустое значение (поле
            // просто не попадёт в тело цикла), чем откатиться на весь инстанс целиком и
            // затянуть в текстовое поле соседние узлы (иконку, другие подписи).
            return '';
        }

        $raw = $type === 'wysiwyg' ? $this->innerHtml($target) : trim($target->textContent);

        // Селектор мог оказаться шире, чем нужно, и захватить соседний узел с иконкой —
        // для текстовых полей это всегда лишнее (иконка тут не поле не по смыслу), вырезаем
        // плейсхолдер ДО восстановления, чтобы в значение не утёк весь SVG-markup.
        $raw = trim(preg_replace('/__ICON_\d+__/', '', $raw));

        return $this->placeholderParser()->restorePlaceholders($raw, $this->placeholders);
    }

    /**
     * Простой резолвер CSS-подобного селектора (tag, tag.class, .class, tag:nth-of-type(N)) —
     * ровно тот набор форм, которые модель реально возвращает для детей repeater'а в наших живых
     * тестах. Сложные селекторы (комбинаторы, псевдоклассы кроме nth-of-type) не поддерживаются —
     * в этом случае возвращаем null; вызывающий код тогда пропускает поле для этой строки, а не
     * подставляет весь инстанс целиком (иначе в текстовое поле утекут соседние узлы — иконка,
     * другие подписи; так уже ловили баг на живых данных, см. Заметки в плане).
     */
    private function resolveSimpleSelector(DOMXPath $xpath, DOMElement $context, string $selector): ?DOMNode{
        $selector = trim($selector);

        // Пустой селектор — единственный случай, когда "это сам инстанс" осмыслен. Совпадение
        // с тегом контекста ("div" при контексте <div class="trading-item">) НЕ считаем этим
        // случаем — почти всегда имеется в виду вложенный <div>, а не сам инстанс.
        if($selector === ''){
            return $context;
        }

        if(!preg_match('/^([a-zA-Z0-9]*)(?:\.([a-zA-Z0-9_-]+))?(?::nth-of-type\((\d+)\))?$/', $selector, $m)){
            return null;
        }

        $tag = $m[1] !== '' ? $m[1] : '*';
        $class = $m[2] ?? '';
        $nth = isset($m[3]) ? (int)$m[3] : 1;

        $query = './/'.$tag;
        if($class !== ''){
            $query .= "[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
        }

        $matches = $xpath->query($query, $context);

        if($matches === false || $matches->length === 0){
            return null;
        }

        return $matches->item(min($nth, $matches->length) - 1);
    }

    /**
     * Значение "листового" (не-repeater) поля верхнего уровня — icon/style/data-плейсхолдеры
     * разворачиваются в реальные значения, для image — резолвится в id записи медиатеки.
     */
    private function resolveLeafValue(string $type, string $rawValue): string{
        if($type === 'icon'){
            return $this->placeholders[$rawValue] ?? $rawValue;
        }

        if($type === 'image'){
            return $this->resolveOembedValue($rawValue);
        }

        // В отличие от resolveChildValue() (детей repeater'а) иконку из значения тут НЕ вырезаем:
        // на верхнем уровне не гарантировано, что для неё есть отдельное соседнее поле типа
        // "icon" — модель иногда сразу отдаёт "текст+иконка" одним узлом. Лучше отрендерить
        // страницу как есть (иконка внутри текстового значения, редактору неудобно её отдельно
        // менять), чем молча потерять эту часть разметки донора.
        return $this->placeholderParser()->restorePlaceholders($rawValue, $this->placeholders);
    }

    /**
     * Из тега <img src="..."> достаёт id записи медиатеки (по url, уже переписанному
     * AssetImporter'ом на этапе 0.4) — это и есть формат значения oembed-поля в этой системе
     * (см. resources/views/admin/pages/fields/oembed.blade.php: значение = id файла).
     */
    private function resolveOembedValue(string $imgTag): string{
        if(!preg_match('/src=["\']([^"\']+)["\']/', $imgTag, $m)){
            return '';
        }

        return (string)($this->imageIdByUrl[$m[1]] ?? '');
    }

    /**
     * Находит все инстансы повторяющегося элемента репитера в разметке по его selector_path.
     * Два поддерживаемых случая:
     *
     * 1. У самого повторяющегося элемента есть класс ("... .trading-wrapper .trading-item" —
     *    последний токен ".trading-item") — глобальный поиск по этому классу, ближайшие токены
     *    селектора (обёртки) игнорируются, класс самого повтора обычно достаточно специфичен.
     *    Исходное поведение, без изменений.
     * 2. У повторяющегося элемента класса НЕТ, только голый тег ("... .about-main__wrapper div",
     *    ".about-main__wrapper > div") — по правилу 9 промпта (`BuildsPageMarkupPrompt`) это
     *    единственный способ ИИ описать повтор классических «голых» карточек без обёртки на
     *    каждый элемент (см. план, «Проверка на втором доноре», находка 2 — репитер без класса на
     *    инстансе раньше вообще не распознавался, `null` уже на этом шаге). Ищем ближайший токен
     *    слева с классом — это контейнер, инстансы — его ПРЯМЫЕ дети с тегом последнего токена.
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
     * Формирует один элемент схемы в целевом формате (тот же, что пишет ручной редактор —
     * adminUpdateTemplateFieldsAction/refreshFieldsKeys).
     */
    private function schemaEntry(string $slug, string $aiType, string $label): object{
        return (object)[
            'id' => $this->generateFieldId(),
            'type' => $this->mapType($aiType),
            'name' => $label,
            'slug' => $slug,
            'instructions' => null,
            'default' => null,
            'conditional_field' => null,
            'conditional_operator' => '==',
            'conditional_value' => null,
        ];
    }

    /**
     * Типы узлов из словаря промпта ИИ (text/wysiwyg/image/icon/repeater) на реальные типы
     * полей системы. "icon" — не отдельный тип поля (см. план, 1.1/1.2), значение — сырой
     * SVG-markup, редактируется как обычный текст без риска, что WYSIWYG-редактор его исказит.
     */
    private function mapType(string $aiType): string{
        return match($aiType){
            'image' => 'oembed',
            'icon' => 'textarea',
            'wysiwyg' => 'wysiwyg',
            'repeater' => 'repeater',
            default => 'text',
        };
    }

    private function generateFieldId(): string{
        static $counter = 0;
        $counter++;

        return (string)((int)(microtime(true) * 1000)).$counter;
    }

    private function uniqueSlug(string $slug): string{
        return $this->uniqueSlugIn($this->usedSlugs, $slug);
    }

    private function uniqueSlugIn(array &$used, string $slug): string{
        $slug = Str::slug($slug, '_') ?: 'field';
        $base = $slug;
        $i = 1;

        while(isset($used[$slug])){
            $i++;
            $slug = $base.'_'.$i;
        }

        $used[$slug] = true;

        return $slug;
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

    private function placeholderParser(): ArchiveParser{
        static $parser = null;
        return $parser ??= new ArchiveParser();
    }
}