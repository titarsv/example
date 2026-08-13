<?php

namespace Modules\ThemeImport\Services;

use Illuminate\Support\Facades\Storage;
use Modules\PageImport\Services\ArchiveParser;
use Modules\PageImport\Services\AssetImporter;

/**
 * Транспланter для 404 — тир полной автоматизации наравне с blog/article (см.
 * docs/dynamic-page-import-plan.md, «Решено: 404 заводится в тему через тонкую стабильную
 * оболочку», шаг 5). В отличие от static-страниц (PageBuilder), НЕ создаёт Page-запись и не
 * строит ACF-схему полей — 404 не CMS-страница, редактируемая через админку, а фиксированный
 * системный вид (`resources/views/errors/404.blade.php` — тонкая оболочка, не меняется импортом),
 * поэтому и ИИ-анализ полей (SchemaBuilder) здесь не нужен — весь контент донора идёт в файл как
 * статичная разметка, один в один, без единого @field()/Blade-выражения.
 * Ассеты (картинки) переносятся в медиатеку тем же AssetImporter, что и static-страницы.
 */
class NotFoundPageBuilder
{
    private ArchiveParser $parser;
    private AssetImporter $assetImporter;

    public function __construct(){
        $this->parser = new ArchiveParser();
        $this->assetImporter = new AssetImporter();
    }

    /**
     * @return string|null Итоговый blade-фрагмент (уже записан в тему), либо null — в разметке
     *         не нашлось контентной области.
     */
    public function build(string $htmlFilePath, string $themeName): ?string{
        $result = $this->parser->parseHtmlFile($htmlFilePath);

        if($result === null){
            return null;
        }

        $imageMap = $this->assetImporter->importImages($result['images']);
        $content = $this->assetImporter->rewriteImageSrcs($result['content'], $imageMap);

        // У 404 нет $seo (не CMS Page — см. класс-докблок) — заголовок, вырезанный ArchiveParser'ом
        // (тот же общий механизм, что у static/blog/article), возвращается литеральным текстом,
        // а не {{ $seo->name }}, донорские класс/атрибуты donor's <h1> при этом теряются (тот же
        // компромисс, что и везде — ArchiveParser отдаёt только сам текст, не узел).
        if(!empty($result['heading'])){
            $heading = htmlspecialchars($result['heading'], ENT_QUOTES, 'UTF-8');
            $content = "<h1 class=\"page-title\">{$heading}</h1>\n".$content;
        }

        Storage::disk('local')->put(
            theme_relative_path('views/public/errors/404_content.blade.php', $themeName),
            $content
        );

        return $content;
    }
}