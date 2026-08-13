<?php

namespace Modules\PageImport\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Разбор одного HTML-файла донора: находит контентную область (по умолчанию <main>)
 * и собирает список картинок внутри неё вместе с относительными путями из разметки.
 *
 * Всё вне контентной области (<head>, <header>, мобильное меню, <footer>, подключения
 * css/js донора) сюда не попадает — этим узлом парсер вообще не интересуется, см. план
 * (docs/page-import-plan.md), стадия 0.3.
 */
class ArchiveParser
{
    /**
     * @param string $htmlFilePath Путь к html-файлу на диске (внутри распакованного архива)
     * @param string $contentSelector Тег-контейнер контента, по умолчанию <main>
     * @return array{
     *     content_selector: string,
     *     content: string,
     *     images: array<int, array{src: string, path: string|null, alt: string}>,
     *     svgs: array<int, string>,
     *     heading: string|null
     * }|null Null, если файл не читается или в нём нет узла $contentSelector
     */
    public function parseHtmlFile(string $htmlFilePath, string $contentSelector = 'main'): ?array{
        if(!is_file($htmlFilePath)){
            return null;
        }

        $html = file_get_contents($htmlFilePath);

        if($html === false || trim($html) === ''){
            return null;
        }

        $dom = new DOMDocument();

        // Подавляем предупреждения о невалидном HTML5 (кастомные атрибуты, необязательные
        // закрывающие теги и т.п. — DOMDocument по умолчанию считает их ошибками).
        $previous = libxml_use_internal_errors(true);
        // Префикс с XML-декларацией — стандартный обход того, что DOMDocument::loadHTML()
        // игнорирует <meta charset> и по умолчанию читает не-ASCII байты как ISO-8859-1.
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $contentNode = $xpath->query('//'.$contentSelector)->item(0);

        if(!($contentNode instanceof DOMElement)){
            return null;
        }

        // <h1> и хлебные крошки донора — не контент шаблона: <h1> идёт в Seo::name (рендерится
        // всеми ручными шаблонами страниц как {{ $seo->name }}), крошки — отдельный, уже
        // подключённый везде модуль (Breadcrumbs::render('page', $page), routes/breadcrumbs.php).
        // Вырезаем ОБА из разметки ДО того, как контент вообще увидит ИИ — иначе он принимает их
        // за обычный контент и заводит под них поля/repeater, дублируя то, что уже даёт сайт.
        $heading = $this->extractHeading($xpath, $contentNode);
        $this->removeBreadcrumbNodes($xpath, $contentNode);

        $images = [];
        foreach($xpath->query('.//img', $contentNode) as $img){
            /** @var DOMElement $img */
            $src = trim($img->getAttribute('src'));

            if($src === ''){
                continue;
            }

            $images[] = [
                'src' => $src,
                'path' => $this->resolveAssetPath(dirname($htmlFilePath), $src),
                'alt' => $img->getAttribute('alt'),
            ];
        }

        $svgs = [];
        foreach($xpath->query('.//svg', $contentNode) as $svg){
            /** @var DOMElement $svg */
            // Пропускаем вложенные <svg> внутри <svg> (паттерны use/symbol) — берём только
            // верхнеуровневые узлы, вложенность уедет вместе с markup родителя как есть.
            if($xpath->query('ancestor::svg', $svg)->length > 0){
                continue;
            }

            $svgs[] = $this->outerHtml($svg);
        }

        return [
            'content_selector' => $contentSelector,
            'content' => $this->innerHtml($contentNode),
            'images' => $images,
            'svgs' => $svgs,
            'heading' => $heading,
        ];
    }

    /**
     * Первый <h1> внутри контентной области — заголовок страницы для Seo::name, а не поле схемы.
     * Узел вырезается из DOM, чтобы дальше по конвейеру (ИИ/SchemaBuilder) его текст не попал
     * в обычное текстовое поле шаблона.
     */
    private function extractHeading(DOMXPath $xpath, DOMElement $contentNode): ?string{
        $node = $xpath->query('.//h1', $contentNode)->item(0);

        if(!($node instanceof DOMElement)){
            return null;
        }

        $heading = trim($node->textContent);
        $node->parentNode->removeChild($node);

        return $heading !== '' ? $heading : null;
    }

    /**
     * Вырезает из контентной области разметку хлебных крошек донора: `<nav aria-label="breadcrumb">`
     * (паттерн, которым пользуется и сам этот проект, см. layouts/breadcrumbs.blade.php) и любой
     * узел с классом "breadcrumb"/"breadcrumbs" (сам контейнер, не отдельные "breadcrumb-item" —
     * они уходят вместе с родителем). Эвристика, не 100% доноров попадёт под неё, но покрывает
     * стандартную Bootstrap-разметку, которой построены оба протестированных донора.
     */
    private function removeBreadcrumbNodes(DOMXPath $xpath, DOMElement $contentNode): void{
        $candidates = $xpath->query(
            './/nav[contains(translate(@aria-label, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "breadcrumb")]'
            .' | .//*[contains(concat(" ", normalize-space(@class), " "), " breadcrumb ")'
            .' or contains(concat(" ", normalize-space(@class), " "), " breadcrumbs ")]',
            $contentNode
        );

        if($candidates === false || $candidates->length === 0){
            return;
        }

        $set = iterator_to_array($candidates);

        foreach($set as $node){
            // Узел, чей предок уже есть в наборе, уйдёт вместе с ним — удалять отдельно не нужно
            // (и небезопасно: могли бы попытаться отсоединить уже отсоединённый узел).
            $ancestor = $node->parentNode;
            $nested = false;
            while($ancestor instanceof DOMElement && $ancestor !== $contentNode){
                if(in_array($ancestor, $set, true)){
                    $nested = true;
                    break;
                }
                $ancestor = $ancestor->parentNode;
            }

            if(!$nested && $node->parentNode !== null){
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Резолвит src картинки донора (обычно относительный, вроде "../assets/static/images/x.jpg")
     * в реальный абсолютный путь на диске. Внешние (http/https), протокол-относительные (//...)
     * и data:-урлы игнорируются — это не файлы архива, переносить в медиатеку нечего.
     */
    private function resolveAssetPath(string $htmlFileDir, string $src): ?string{
        $src = preg_replace('/[?#].*$/', '', $src);

        if(empty($src) || str_starts_with($src, 'data:') || str_starts_with($src, '//') || preg_match('#^[a-z][a-z0-9+.-]*://#i', $src)){
            return null;
        }

        $resolved = $htmlFileDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $src);
        $real = realpath($resolved);

        return $real !== false ? $real : null;
    }

    /**
     * Сериализует дочерние узлы DOM-элемента обратно в HTML-строку (аналог innerHTML в браузере).
     */
    private function innerHtml(DOMNode $node): string{
        $html = '';

        foreach($node->childNodes as $child){
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    /**
     * Сериализует сам узел вместе с его разметкой (аналог outerHTML в браузере).
     */
    private function outerHtml(DOMNode $node): string{
        return $node->ownerDocument->saveHTML($node);
    }

    /**
     * Готовит контент к отправке в ИИ (стадия 1.1, см. план): прячет геометрию инлайн-SVG,
     * инлайн-стили и длинные data:-урлы за короткими плейсхолдерами. Это не про экономию места —
     * это про то, чтобы модель не пыталась "пересказать" координаты путей SVG вместо разметки
     * смысловых полей и не путала обвязку с контентом. Оригиналы возвращаются картой для точной
     * обратной подстановки при сборке значений полей (см. 1.3).
     *
     * @param string $content HTML-контент (ArchiveParser::parseHtmlFile()['content'])
     * @param array<int, string> $svgs Список markup инлайн-SVG из того же parseHtmlFile()
     * @return array{content: string, placeholders: array<string, string>}
     */
    public function preprocessForAi(string $content, array $svgs): array{
        $placeholders = [];

        $content = $this->placeholderizeSvgs($content, $svgs, $placeholders);
        $content = $this->placeholderizeStyleAttributes($content, $placeholders);
        $content = $this->placeholderizeLongDataUris($content, $placeholders);

        return ['content' => $content, 'placeholders' => $placeholders];
    }

    /**
     * Разворачивает плейсхолдеры preprocessForAi() обратно в оригинальные значения — один проход
     * через strtr(), без риска, что подставленный markup сам "случайно" содержит чужой плейсхолдер
     * и его подхватит повторный проход (в отличие от последовательных str_replace).
     */
    public function restorePlaceholders(string $content, array $placeholders): string{
        return strtr($content, $placeholders);
    }

    /**
     * Заменяет каждый уникальный SVG-markup из списка на __ICON_N__ (одинаковые иконки внутри
     * одного контента получают один и тот же плейсхолдер — эквивалент дедупа на уровне разметки).
     */
    private function placeholderizeSvgs(string $content, array $svgs, array &$placeholders): string{
        $seen = [];
        $counter = 0;

        foreach($svgs as $markup){
            $markup = trim($markup);

            if($markup === '' || isset($seen[$markup])){
                continue;
            }

            $counter++;
            $key = '__ICON_'.$counter.'__';
            $seen[$markup] = $key;
            $placeholders[$key] = $markup;
        }

        foreach($seen as $markup => $key){
            $content = str_replace($markup, $key, $content);
        }

        return $content;
    }

    /**
     * Заменяет значение каждого атрибута style="..." на __STYLE_N__.
     */
    private function placeholderizeStyleAttributes(string $content, array &$placeholders): string{
        $counter = 0;

        return preg_replace_callback('/\sstyle=(["\'])(.*?)\1/is', function($match) use (&$placeholders, &$counter){
            $counter++;
            $key = '__STYLE_'.$counter.'__';
            $placeholders[$key] = $match[2];

            return ' style='.$match[1].$key.$match[1];
        }, $content);
    }

    /**
     * Заменяет длинные data:-урлы (встроенные base64-картинки в src/href) на __DATA_N__.
     */
    private function placeholderizeLongDataUris(string $content, array &$placeholders): string{
        $counter = 0;

        return preg_replace_callback('/data:[a-zA-Z0-9\/+;=,.\-]{40,}/', function($match) use (&$placeholders, &$counter){
            $counter++;
            $key = '__DATA_'.$counter.'__';
            $placeholders[$key] = $match[0];

            return $key;
        }, $content);
    }
}