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

        copy($pagePartial, $targetDir.DIRECTORY_SEPARATOR.'_'.$pageName.'.scss');

        $imports = ["@import '{$pageName}';"];

        foreach(self::SHARED_PARTIALS as $shared){
            $sharedFile = $stylesheetsDir.DIRECTORY_SEPARATOR.'_'.$shared.'.scss';

            if(is_file($sharedFile)){
                copy($sharedFile, $targetDir.DIRECTORY_SEPARATOR.'_'.$shared.'.scss');
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
