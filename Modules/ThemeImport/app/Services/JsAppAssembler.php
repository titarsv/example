<?php

namespace Modules\ThemeImport\Services;

/**
 * Собирает js/app.js новой темы из донорского app/app.js — см.
 * docs/dynamic-page-import-plan.md, «JS» (в разделе «Источник архива»).
 *
 * Правки — минимальные, только то, что реально нужно для успешной сборки/подключения бизнес-
 * логики:
 * 1. SCSS-импорт внутри JS донора (`import './assets/stylesheets/app.scss';`) — вырезается:
 *    единственная правка, без которой сборка гарантированно упадёт (путь ведёт туда, куда мы
 *    больше ничего не копируем — SCSS разложен отдельно per-page, см. AssetPlacer).
 * 2. Глубина `../` до `node_modules` в явных относительных require — пересчитывается (было
 *    1 уровень от app/app.js, станет 4 от resources/themes/{theme}/js/app.js — как уже сейчас у
 *    scss/app.scss, см. resources/themes/base/scss/app.scss).
 * 3. Реальный блок инициализации jQuery/Bootstrap 5 (тот же, что в начале
 *    resources/themes/base/js/app.js) — ставится ПЕРЕД контентом донора, а не заменяет его.
 *    Donor's собственный require('bootstrap-sass') после этого не вредит — bootstrap-sass не
 *    переопределяет глобальный jQuery-неймспейс, которым пользуется остальной код сайта (не
 *    монки-патчит $.fn.*, в отличие от того, как это делала бы более старая версия без namespace).
 *    А вот donor's `let $ = require('jquery')` (реальный, живьём встреченный паттерн, не
 *    гипотеза) — вредит: `let $` в vendorInitBlock() и donor's `let $` оказываются в ОДНОМ
 *    модульном scope после конкатенации, повторное `let`-объявление одного имени — не просто
 *    избыточность, а SyntaxError у babel/webpack. Присваивание вырезается точечно (см.
 *    stripDonorJqueryDeclaration()), сам require() остаётся — вдруг донор полагается на его
 *    побочный эффект (плагины, вешающиеся на $.fn при загрузке).
 * 4. `require('.../shop')` — бизнес-логика магазина (корзина/фильтры/чекаут), которую сам донор
 *    никогда не может дать — дописывается в конец. Тот же принцип, что уже видели живьём в
 *    C:\OSPanel\home\nnn-site.lh\public\resources\js\app.js (app.js -> custom.js -> larchik/*).
 */
class JsAppAssembler
{
    public function assemble(string $donorAppJsPath, string $baseThemeName = 'base'): string
    {
        $content = file_get_contents($donorAppJsPath);

        if($content === false){
            throw new \RuntimeException("Could not read {$donorAppJsPath}");
        }

        $content = $this->stripScssImports($content);
        $content = $this->stripDonorJqueryDeclaration($content);
        $content = $this->rebaseNodeModulesDepth($content);

        return $this->vendorInitBlock()."\n".rtrim($content)."\n\n".$this->shopRequireBlock($baseThemeName);
    }

    /**
     * Вырезает строки вида `import '...something.scss';`/`require('...something.scss')` —
     * единственная правка, без которой webpack не соберёт файл вовсе (путь недостижим на новом
     * месте, SCSS раскладывается отдельно, см. AssetPlacer).
     */
    private function stripScssImports(string $content): string
    {
        // \r?$ — донорские файлы у обоих протестированных доноров в CRLF (\r\n): без \r? перед
        // концом строки в multiline-режиме совпадение срывалось на висящем \r (живой баг,
        // пойманный именно на этом прогоне, не гипотеза).
        return preg_replace('/^.*[\'"][^\'"\n]*\.scss[\'"];?[ \t]*\r?$/mi', '', $content) ?? $content;
    }

    /**
     * `let/const/var $ = require('jquery')` → `require('jquery');` — снимает конфликтующее
     * присваивание, оставляя сам вызов (см. пункт 3 в докблоке класса). Другие имена (donor мог
     * назвать переменную не `$`) не трогаются — конфликта с vendorInitBlock() у них нет.
     */
    private function stripDonorJqueryDeclaration(string $content): string
    {
        return preg_replace(
            '/\b(?:let|const|var)\s+\$\s*=\s*(require\((["\'])jquery\2\))\s*;?/',
            '$1;',
            $content
        ) ?? $content;
    }

    /**
     * `require('../node_modules/x')` (донорская глубина — app/app.js, 1 уровень до корня) →
     * `require('../../../../node_modules/x')` (resources/themes/{theme}/js/app.js, 4 уровня).
     * Бэйр-реквайры (`require('jquery')`) не трогаются — они резолвятся обычным node_modules
     * lookup'ом независимо от расположения файла, углублять там нечего.
     */
    private function rebaseNodeModulesDepth(string $content): string
    {
        return preg_replace_callback(
            '/require\((["\'])(?:\.\.\/)+node_modules\//',
            fn(array $m) => "require({$m[1]}../../../../node_modules/",
            $content
        ) ?? $content;
    }

    /**
     * Тот же блок, что и в начале resources/themes/base/js/app.js — реальный jQuery/Bootstrap 5
     * (не donor's bootstrap-sass) + CSRF для AJAX. Донор мог называть переменную иначе или вовсе
     * не задавать её — эта копия гарантированно рабочая, независимо от того, что было у донора.
     */
    private function vendorInitBlock(): string
    {
        return <<<'JS'
import $ from 'jquery';
import * as bootstrap from 'bootstrap';

window.$ = window.jQuery = $;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
});

JS;
    }

    /**
     * Из resources/themes/{new-theme}/js/app.js до resources/themes/{base}/js/shop — на два
     * уровня вверх (из js/, из {new-theme}/), затем в {base}/js/shop. Реэкспортирует site.js тоже
     * (FAQ-таб/форма отзыва/слайдер рекомендаций/универсальные ajax-формы) — новая тема не
     * переопределяет разметку страниц, унаследованных от base, значит их JS-поведение тоже нужно.
     */
    private function shopRequireBlock(string $baseThemeName): string
    {
        return "require('../../{$baseThemeName}/js/shop');\nrequire('../../{$baseThemeName}/js/site');\n";
    }
}