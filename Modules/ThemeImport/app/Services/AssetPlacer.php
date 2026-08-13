<?php

namespace Modules\ThemeImport\Services;

/**
 * Раскладка ассетов донора (images/fonts/scss) по новой теме — см.
 * docs/dynamic-page-import-plan.md, «Источник архива: не dist/, а исходники верстальщика».
 */
class AssetPlacer
{
    /**
     * Общие partial'ы, которые может использовать SCSS конкретной страницы (не сама специфика
     * страницы) — фиксированный короткий список вместо попытки статически разобрать граф @import.
     * `_vendor.scss` намеренно исключён — у обоих протестированных доноров он тянет отдельную
     * полную копию скомпилированного Bootstrap (`node_modules/bootstrap/dist/css/bootstrap.min`),
     * а сама тема уже даёт настоящий Bootstrap 5 через свой app.scss — второй копией стало бы
     * просто лишним весом, ничего не сломав, но и не дав ничего полезного.
     */
    private const SHARED_PARTIALS = ['variables', 'mixin', 'fonts'];

    /**
     * Копирует images/fonts донора в тему целиком, в подпапку imported/ (чтобы не перезаписать
     * favicon.png/логотип темы одноимённым файлом донора). mix.copyDirectory() в webpack.mix.js
     * копирует всю images/fonts директорию как есть — imported/ внутри неё уедет вместе со всем
     * остальным без каких-либо дополнительных правок сборки.
     */
    public function placeImagesAndFonts(?string $imagesDir, ?string $fontsDir, string $themeName): void
    {
        if($imagesDir !== null && is_dir($imagesDir)){
            $this->copyDir($imagesDir, theme_path('images/imported', $themeName));
        }

        if($fontsDir !== null && is_dir($fontsDir)){
            $this->copyDir($fontsDir, theme_path('fonts/imported', $themeName));
        }
    }

    /**
     * Копирует SCSS конкретной страницы (если у неё есть donor's `_{name}.scss`) + нужные общие
     * partial'ы в изолированную подпапку `scss/imported/{name}/` и пишет `entry.scss`, который их
     * связывает — НЕ участвует в общем `app.scss` темы (см. план — коллизия SCSS-переменных между
     * donor's `_variables.scss` и темы). Вызывается независимо от того, есть ли уже транспланter
     * для типа этой страницы — раскладка ассетов не обязана дожидаться готового трансплантера.
     *
     * @return bool true, если для этой страницы вообще нашёлся SCSS (не у каждой страницы он есть).
     */
    public function placeStylesheetsForPage(string $stylesheetsDir, string $pageName, string $themeName): bool
    {
        $pagePartial = $stylesheetsDir.DIRECTORY_SEPARATOR.'_'.$pageName.'.scss';

        if(!is_file($pagePartial)){
            return false;
        }

        $targetDir = theme_path("scss/imported/{$pageName}", $themeName);

        if(!is_dir($targetDir)){
            mkdir($targetDir, 0777, true);
        }

        file_put_contents(
            $targetDir.DIRECTORY_SEPARATOR.'_'.$pageName.'.scss',
            $this->rewriteRelativeAssetUrls(file_get_contents($pagePartial))
        );

        $imports = ["@import '{$pageName}';"];

        foreach(self::SHARED_PARTIALS as $shared){
            $sharedFile = $stylesheetsDir.DIRECTORY_SEPARATOR.'_'.$shared.'.scss';

            if(is_file($sharedFile)){
                file_put_contents(
                    $targetDir.DIRECTORY_SEPARATOR.'_'.$shared.'.scss',
                    $this->rewriteRelativeAssetUrls(file_get_contents($sharedFile))
                );
                array_unshift($imports, "@import '{$shared}';");
            }
        }

        file_put_contents(
            $targetDir.DIRECTORY_SEPARATOR.'entry.scss',
            "// Автосгенерировано Modules\\ThemeImport — изолированный entry конкретно этой страницы,\n"
            ."// НЕ участвует в общем app.scss темы. См. docs/dynamic-page-import-plan.md.\n"
            .implode("\n", $imports)."\n"
        );

        return true;
    }

    /**
     * Кладёт ОДНУ каноническую копию общих partial'ов (variables/mixin/fonts) в
     * `scss/imported/_shared/` — независимо от per-page копий, которые placeStylesheetsForPage()
     * кладёт КАЖДОЙ странице отдельно (та изоляция намеренная, см. её докблок). Эта копия — цель
     * для rewriteModuleScssImports(): donor's `js/modules/**\/*.scss` (forms/popup/slider и т.п.,
     * см. её докблок) ссылаются на `assets/stylesheets/variables` в расчёте на СВОЮ, соседнюю
     * структуру донора, которой в теме не существует — нужно на что-то их переписать.
     * `_shared/` — та же глубина, что и `scss/imported/{page}/` (два уровня под `scss/`), поэтому
     * тот же rewriteRelativeAssetUrls() (жёстко на 3 уровня) корректен и здесь.
     */
    public function placeSharedPartials(string $stylesheetsDir, string $themeName): void
    {
        $targetDir = theme_path('scss/imported/_shared', $themeName);

        if(!is_dir($targetDir)){
            mkdir($targetDir, 0777, true);
        }

        foreach(self::SHARED_PARTIALS as $shared){
            $sharedFile = $stylesheetsDir.DIRECTORY_SEPARATOR.'_'.$shared.'.scss';

            if(is_file($sharedFile)){
                file_put_contents(
                    $targetDir.DIRECTORY_SEPARATOR.'_'.$shared.'.scss',
                    $this->rewriteRelativeAssetUrls(file_get_contents($sharedFile))
                );
            }
        }
    }

    /**
     * Переписывает `@import` в скопированных donor's `js/modules/**\/*.scss` (см. copyDir() вызов
     * для `modules/` в ProcessThemeImportJob — donor's типовые UI-виджеты типа forms/popup/slider,
     * почти у каждого донора, почти всегда со своим SCSS). Два вида путей, оба недостижимы на новом
     * месте без пересчёта:
     * 1. `../../assets/stylesheets/variables` (donor's ОРИГИНАЛЬНАЯ структура — `modules/` и
     *    `stylesheets/` были соседями) → `scss/imported/_shared/variables` (см.
     *    placeSharedPartials()).
     * 2. `../../../node_modules/x` → фактический node_modules ПРОЕКТА (не архива донора).
     * В отличие от JsAppAssembler::rebaseNodeModulesDepth() (там ровно один файл, `js/app.js`,
     * глубина фиксирована), здесь модули лежат на РАЗНОЙ глубине — глубина каждого файла
     * пересчитывается от его фактического положения, а не захардкожена.
     * Живой баг: без этого шага `npm run dev`/`prod` падает с "Can't find stylesheet to import" на
     * первом же донорском модуле со своим SCSS — раньше не ловилось, потому что тестировалось
     * только существование файлов, не реальная сборка ими webpack'ом.
     */
    public function rewriteModuleScssImports(string $themeName): void
    {
        $modulesDir = theme_path('js/modules', $themeName);

        if(!is_dir($modulesDir)){
            return;
        }

        $themeRoot = theme_path('', $themeName);

        foreach($this->findFiles($modulesDir, 'scss') as $file){
            $depthToTheme = $this->relativeDepth(dirname($file), $themeRoot);
            $depthToProject = $this->relativeDepth(dirname($file), base_path());
            $upToTheme = str_repeat('../', $depthToTheme);
            $upToProject = str_repeat('../', $depthToProject);

            $content = file_get_contents($file);

            $content = preg_replace_callback(
                '/@import\s+(["\'])(?:\.\.\/)+assets\/stylesheets\/([^"\']+)\1/',
                fn($m) => "@import {$m[1]}{$upToTheme}scss/imported/_shared/{$m[2]}{$m[1]}",
                $content
            );

            $content = preg_replace_callback(
                '/@import\s+(["\'])(?:\.\.\/)+node_modules\/([^"\']+)\1/',
                fn($m) => "@import {$m[1]}{$upToProject}node_modules/{$m[2]}{$m[1]}",
                $content
            );

            file_put_contents($file, $content);
        }
    }

    /**
     * Число уровней "../" от директории $from до директории $root (обе — реальные пути на диске).
     * 0, если $from не вложена в $root (сохраняем содержимое как есть — лучше не тронуть путь,
     * чем сломать его на пустом месте).
     */
    private function relativeDepth(string $from, string $root): int
    {
        $from = rtrim(str_replace('\\', '/', realpath($from) ?: $from), '/');
        $root = rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/');

        if($from === $root || !str_starts_with($from, $root.'/')){
            return 0;
        }

        $remainder = trim(substr($from, strlen($root)), '/');

        return $remainder === '' ? 0 : substr_count($remainder, '/') + 1;
    }

    /**
     * Рекурсивно находит файлы с заданным расширением под $dir.
     *
     * @return string[]
     */
    private function findFiles(string $dir, string $extension): array
    {
        $found = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach($iterator as $fileInfo){
            if($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === $extension){
                $found[] = $fileInfo->getPathname();
            }
        }

        return $found;
    }

    /**
     * Переписывает относительные `url(...)` на donor's `fonts`/`images` в скопированном SCSS —
     * без этого сборка (webpack) не резолвит их вовсе (ловится только на реальном `npm run dev`/
     * `prod`, не raw-проверкой существования файлов — так и найден живьём). Donor's исходный SCSS
     * (`_fonts.scss`, `_{page}.scss`) писался для СВОЕЙ, соседней структуры (`stylesheets/` рядом
     * с `fonts/`/`images/`), а копия уезжает на 3 уровня глубже —
     * `scss/imported/{page}/` вместо `stylesheets/` — при этом сами fonts/images лежат в
     * `fonts/imported/`/`images/imported/` (см. placeImagesAndFonts(), подпапка imported/ — чтобы
     * не перезаписать favicon/лого темы одноимённым файлом донора). Donor's исходный префикс
     * `../` (какой бы глубины он ни был у конкретного донора) заменяется на фактический путь от
     * новой глубины, а не жёстко зашитой строкой.
     */
    private function rewriteRelativeAssetUrls(string $scss): string
    {
        return preg_replace_callback(
            '/url\((["\']?)(?:\.\.\/)+(fonts|images)\/([^)"\']+)\1\)/',
            fn($m) => "url({$m[1]}../../../{$m[2]}/imported/{$m[3]}{$m[1]})",
            $scss
        );
    }

    /**
     * Рекурсивное копирование директории — простая реализация вместо Illuminate\Filesystem
     * намеренно: обе стороны (источник — распакованный архив в storage/app, назначение —
     * resources/themes/{theme}) настоящие пути на диске, Storage-абстракция тут не нужна.
     */
    public function copyDir(string $source, string $target): void
    {
        if(!is_dir($target)){
            mkdir($target, 0777, true);
        }

        foreach(scandir($source) as $item){
            if($item === '.' || $item === '..'){
                continue;
            }

            $sourcePath = $source.DIRECTORY_SEPARATOR.$item;
            $targetPath = $target.DIRECTORY_SEPARATOR.$item;

            if(is_dir($sourcePath)){
                $this->copyDir($sourcePath, $targetPath);
            }else{
                copy($sourcePath, $targetPath);
            }
        }
    }
}
