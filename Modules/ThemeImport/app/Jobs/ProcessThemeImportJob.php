<?php

namespace Modules\ThemeImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Ai\Services\AiServiceInterface;
use Modules\PageImport\Services\PageBuilder;
use Modules\ThemeImport\Models\ThemeImport;
use Modules\ThemeImport\Services\AssetPlacer;
use Modules\ThemeImport\Services\DynamicPageTransplanter;
use Modules\ThemeImport\Services\JsAppAssembler;
use Modules\ThemeImport\Services\NotFoundPageBuilder;
use Modules\ThemeImport\Services\PageTypeClassifier;
use Modules\ThemeImport\Services\SourceLocator;
use Modules\ThemeImport\Services\ThemeForker;

/**
 * Оркестратор импорта темы (docs/dynamic-page-import-plan.md): классифицирует каждую найденную
 * app/assets/templates/layouts/*.html страницу донора, создаёт новую тему (theme:make), раскладывает
 * images/fonts/scss по местам, собирает js/app.js из donor's app/app.js. Дальше по типу страницы:
 * `static` — шаблон+Page тем же путём, что и Modules\PageImport (PageBuilder, целевая тема
 * параметризована); `blog`/`article` — DynamicPageTransplanter переопределяет соответствующий
 * маршрут темы (см. план, шаг 2). Для остальных типов (catalog/product/search — шаг 3;
 * checkout/thanks — намеренно никогда) транспланter ещё не реализован — ассеты всё равно
 * раскладываются, но файл шаблона не генерируется.
 */
class ProcessThemeImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Несколько живых вызовов ИИ подряд (по одному на static-страницу) — 30 минут с запасом. */
    public int $timeout = 1800;

    /** Повторный запуск задвоил бы уже созданную тему/страницы — при ошибке разбираемся вручную. */
    public int $tries = 1;

    public function __construct(private readonly int $themeImportId){
    }

    public function handle(): void{
        $import = ThemeImport::find($this->themeImportId);

        if(empty($import)){
            return;
        }

        $import->update(['status' => ThemeImport::STATUS_PROCESSING]);

        $located = (new SourceLocator())->locate($import->extractedPath());

        if($located === null){
            $this->fail_($import, trans('locale.theme_import.source_not_found'));
            return;
        }

        $htmlFiles = glob($located['layouts'].DIRECTORY_SEPARATOR.'*.html') ?: [];

        if(empty($htmlFiles)){
            $this->fail_($import, trans('locale.theme_import.no_layouts_found'));
            return;
        }

        $classifier = new PageTypeClassifier();
        $ai = app(AiServiceInterface::class);
        $pages = [];

        foreach($htmlFiles as $file){
            $baseName = pathinfo($file, PATHINFO_FILENAME);

            if($classifier->isSkipped($baseName)){
                $pages[] = ['file' => $baseName.'.html', 'name' => $baseName, 'type' => null, 'classified_by' => 'skip'];
                continue;
            }

            $type = $classifier->matchByName($baseName);
            $classifiedBy = 'heuristic';

            if($type === null){
                // Эвристика по имени не уверена — узкий, дешёвый ИИ-фоллбек (см.
                // docs/dynamic-page-import-plan.md, «Классификация типа страницы»). Неудача
                // здесь не критична — просто трактуем как static, тот же безопасный дефолт,
                // что был бы и без ИИ-фоллбека вовсе.
                $type = $this->classifyByAi($ai, $file, $baseName);
                $classifiedBy = $type !== null ? 'ai' : 'default';
                $type ??= PageTypeClassifier::TYPE_STATIC;
            }

            $pages[] = ['file' => $baseName.'.html', 'name' => $baseName, 'type' => $type, 'classified_by' => $classifiedBy];
        }

        $import->update(['pages' => $pages]);

        try {
            $themeName = (new ThemeForker())->fork($import->name);
        } catch(\Throwable $e){
            Log::error("ThemeImport #{$import->id}: theme:make failed: ".$e->getMessage());
            $this->fail_($import, trans('locale.theme_import.theme_make_failed', ['message' => $e->getMessage()]));
            return;
        }

        $import->update(['theme_name' => $themeName]);

        $assetPlacer = new AssetPlacer();
        $assetPlacer->placeImagesAndFonts($located['images'], $located['fonts'], $themeName);

        if($located['modules'] !== null){
            $assetPlacer->copyDir($located['modules'], theme_path('js/modules', $themeName));
        }

        if($located['appJs'] !== null){
            file_put_contents(theme_path('js/app.js', $themeName), (new JsAppAssembler())->assemble($located['appJs']));
        }

        $pageBuilder = new PageBuilder();
        $transplanter = new DynamicPageTransplanter();
        $notFoundBuilder = new NotFoundPageBuilder();
        // Типы, для которых уже есть транспланter (см. план, шаг 2 — blog/article, шаг 3 —
        // catalog/search; product пока отложен — см. план). checkout/thanks сюда никогда не
        // попадут — по решению плана транспланter для них не строится в принципе. 404 — не в этом
        // списке, у него свой отдельный builder (NotFoundPageBuilder, не Page-запись, см. план,
        // шаг 5).
        $transplantableTypes = [
            PageTypeClassifier::TYPE_BLOG,
            PageTypeClassifier::TYPE_ARTICLE,
            PageTypeClassifier::TYPE_CATALOG,
            PageTypeClassifier::TYPE_SEARCH,
        ];
        $built = [];
        $created = 0;
        $errors = 0;

        foreach($pages as $page){
            if($page['type'] === null){
                continue; // служебная страница донора (403/500/error) — не обрабатывается вовсе
            }

            $scssPlaced = $assetPlacer->placeStylesheetsForPage($located['stylesheets'], $page['name'], $themeName);
            $fullPath = $located['layouts'].DIRECTORY_SEPARATOR.$page['file'];

            if($page['type'] === PageTypeClassifier::TYPE_STATIC){
                try {
                    $result = $pageBuilder->build($fullPath, $page['file'], [], $themeName);
                } catch(\Throwable $e){
                    Log::error("ThemeImport #{$import->id}: ошибка сборки страницы {$page['file']}: ".$e->getMessage());
                    $result = ['status' => 'error', 'file' => $page['file'], 'error' => 'exception'];
                }
            }elseif($page['type'] === PageTypeClassifier::TYPE_404){
                $result = $this->build404Page($notFoundBuilder, $fullPath, $page, $themeName);
            }elseif(in_array($page['type'], $transplantableTypes, true)){
                $result = $this->transplantDynamicPage($transplanter, $fullPath, $page, $themeName);
            }else{
                // Транспланter для этого типа ещё не реализован (см. план, шаги 3–4) — ассеты
                // разложены (выше), сам файл шаблона намеренно не генерируется.
                $result = ['status' => 'not_implemented', 'file' => $page['file'], 'name' => $page['name']];
            }

            $result['type'] = $page['type'];
            $result['classified_by'] = $page['classified_by'] ?? 'heuristic';
            $result['scss_placed'] = $scssPlaced;

            if($result['status'] === 'created'){
                $created++;
            }elseif($result['status'] === 'error'){
                $errors++;
            }

            $built[] = $result;

            // Сохраняем прогресс после каждой страницы — как и в Modules\PageImport (см.
            // ProcessPageImportJob), если импорт из многих страниц упадёт на середине, уже
            // собранные страницы не потеряются.
            $import->update(['built_pages' => $built]);
        }

        $log = $import->log ?? [];
        $log[] = trans('locale.theme_import.build_summary', ['created' => $created, 'errors' => $errors, 'theme' => $themeName]);

        $import->update([
            'status' => ThemeImport::STATUS_REVIEW,
            'log' => $log,
            'built_pages' => $built,
        ]);
    }

    /**
     * ИИ-фоллбек классификации типа страницы — вызывается только когда эвристика по имени файла
     * не уверена (PageTypeClassifier::matchByName() === null). Не нужен чистый разбор <main>, как
     * для полной сборки static-страницы (ArchiveParser) — дёшево читаем сырой файл целиком,
     * вырезаем script/style и обрезаем: для классификации достаточно первых ~4000 символов,
     * гонять весь HTML through ИИ ради одного слова смысла нет.
     */
    private function classifyByAi(AiServiceInterface $ai, string $filePath, string $baseName): ?string{
        $html = @file_get_contents($filePath);

        if($html === false){
            return null;
        }

        $snippet = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $snippet = mb_substr($snippet, 0, 4000);

        return $ai->classifyPageType($snippet, ['file_name' => $baseName.'.html']);
    }

    /**
     * blog/article (см. план, шаг 2) — в отличие от static, это не Page-запись, а переопределение
     * основного маршрута темы (resources/themes/{theme}/views/public/{type}.blade.php), поэтому
     * результат пишется напрямую файлом, без Page/Setting — DynamicPageTransplanter уже отдаёт
     * полностью собранный Blade.
     */
    private function transplantDynamicPage(DynamicPageTransplanter $transplanter, string $fullPath, array $page, string $themeName): array{
        try {
            $blade = $transplanter->build($fullPath, $page['type']);
        } catch(\Throwable $e){
            Log::error("ThemeImport: ошибка транспланта {$page['file']}: ".$e->getMessage());
            return ['status' => 'error', 'file' => $page['file'], 'error' => 'exception'];
        }

        if($blade === null){
            return ['status' => 'error', 'file' => $page['file'], 'error' => 'no_slots_matched'];
        }

        Storage::disk('local')->put(
            theme_relative_path("views/public/{$page['type']}.blade.php", $themeName),
            $blade
        );

        return ['status' => 'created', 'file' => $page['file'], 'name' => $page['name']];
    }

    /**
     * 404 (см. план, шаг 5) — как и blog/article, не Page-запись, а файл темы
     * (`views/public/errors/404_content.blade.php`, подключаемый тонкой стабильной оболочкой
     * `resources/views/errors/404.blade.php`, которую импорт больше не трогает).
     */
    private function build404Page(NotFoundPageBuilder $builder, string $fullPath, array $page, string $themeName): array{
        try {
            $content = $builder->build($fullPath, $themeName);
        } catch(\Throwable $e){
            Log::error("ThemeImport: ошибка сборки 404 {$page['file']}: ".$e->getMessage());
            return ['status' => 'error', 'file' => $page['file'], 'error' => 'exception'];
        }

        if($content === null){
            return ['status' => 'error', 'file' => $page['file'], 'error' => 'no_main_found'];
        }

        return ['status' => 'created', 'file' => $page['file'], 'name' => $page['name']];
    }

    private function fail_(ThemeImport $import, string $message): void{
        $log = $import->log ?? [];
        $log[] = $message;
        $import->update(['status' => ThemeImport::STATUS_ERROR, 'log' => $log]);
    }
}