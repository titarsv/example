<?php

namespace App\Http\Controllers\Concerns;

/**
 * Разное чтение метаданных шаблона страницы/блока поверх settings/JSON —
 * обнаружение "битых" ссылок (template хранится на странице/блоке как строка,
 * независимо от файла на диске и от схемы полей в settings — если файл
 * переименовали/удалили в обход админки, страница/блок молча падает при
 * рендере на публичной части) и чтение категории для списка шаблонов.
 */
trait DetectsMissingTemplates
{
    /**
     * Значения template, на которые ссылаются записи $entity_class, но для
     * которых нет файла среди $existing_paths.
     *
     * @param string $entity_class App\Models\Page::class | App\Models\Block::class
     * @param string[] $existing_paths пути шаблонов, для которых blade-файл реально существует
     * @param string $skip_template значение template, не требующее файла (например 'public.page')
     * @return \Illuminate\Support\Collection
     */
    protected function missingTemplates(string $entity_class, array $existing_paths, string $skip_template){
        return $entity_class::whereNotNull('template')
            ->where('template', '!=', $skip_template)
            ->whereNotIn('template', $existing_paths)
            ->selectRaw('template, count(*) as entries_count')
            ->groupBy('template')
            ->get();
    }

    /**
     * Категория шаблона (settings, с fallback на Local JSON — см. 1.6) для строки
     * в списке "Шаблоны страниц"/"Шаблоны блоков".
     *
     * @param \App\Models\Setting $settings
     * @param string $path полный путь шаблона, например 'public.layouts.pages.about'
     * @param string $name короткое имя, например 'about'
     * @param string $type 'pages' | 'blocks'
     * @return string
     */
    protected function templateCategory($settings, string $path, string $name, string $type): string {
        $template = $settings->get_setting('template_'.$path);

        if(empty($template)){
            $json_path = theme_relative_path("views/public/layouts/$type/$name.fields.json");
            if(\Illuminate\Support\Facades\Storage::disk('local')->exists($json_path)){
                $template = json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get($json_path));
            }
        }

        return !empty($template) && !empty($template->category) ? $template->category : '';
    }
}