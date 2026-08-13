<?php

namespace Modules\ThemeImport\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Обёртка над уже существующей `php artisan theme:make` (см. App\Console\Commands\ThemeMakeCommand)
 * — переиспользуем протестированный код команды вместо своей копии логики копирования темы.
 */
class ThemeForker
{
    /**
     * @return string Слаг реально созданной темы (может отличаться от предложенного $preferredName
     *         при коллизии имени — см. uniqueName()).
     */
    public function fork(string $preferredName, string $from = 'base'): string
    {
        $name = $this->uniqueName($preferredName);

        $exitCode = Artisan::call('theme:make', ['name' => $name, '--from' => $from]);

        if($exitCode !== 0){
            throw new \RuntimeException('theme:make failed: '.Artisan::output());
        }

        $this->removeRedundantShopJs($name, $from);

        return $name;
    }

    /**
     * theme:make копирует ВСЁ содержимое $from буквально — включая js/shop/ и js/site.js, если
     * $from уже содержит их (у base — содержит, см. docs/dynamic-page-import-plan.md, п.0.1).
     * JsAppAssembler всегда ссылается на них через относительный путь до $from
     * (`require('../../{$from}/js/shop')`), не на локальную копию — значит скопированная копия
     * никогда не используется, просто мёртвый вес. Не удаляем, если $from совпадает с только что
     * созданной темой (защита от вырожденного случая, в реальности не должно происходить) — тогда
     * это была бы единственная реальная копия.
     */
    private function removeRedundantShopJs(string $name, string $from): void
    {
        if($name === $from){
            return;
        }

        $shopDir = theme_path('js/shop', $name);
        $siteJs = theme_path('js/site.js', $name);

        if(is_dir($shopDir)){
            (new \Illuminate\Filesystem\Filesystem())->deleteDirectory($shopDir);
        }

        if(is_file($siteJs)){
            unlink($siteJs);
        }
    }

    /**
     * theme:make сам отказывается перезаписать существующую тему (см. ThemeMakeCommand) — вместо
     * того чтобы просто упасть при повторном импорте с тем же именем архива, подбираем свободный
     * слаг тем же принципом, что PageBuilder::uniqueTemplateName() уже делает для шаблонов.
     */
    private function uniqueName(string $preferredName): string
    {
        $base = Str::slug($preferredName, '-') ?: 'theme';
        $themesPath = config('theme.path');

        if(!is_dir($themesPath.'/'.$base)){
            return $base;
        }

        $i = 2;

        do{
            $name = $base.'-'.$i;
            $i++;
        }while(is_dir($themesPath.'/'.$name));

        return $name;
    }
}