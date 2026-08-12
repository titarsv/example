<?php

namespace Modules\PageImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\PageImport\Models\PageImport;
use Modules\PageImport\Services\PageBuilder;

/**
 * Тяжёлая часть импорта (ИИ-разбор + заливка ассетов по каждой найденной html-странице
 * архива) — в очереди, а не в HTTP-запросе загрузки. См. план, стадия 2.3.
 */
class ProcessPageImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Несколько живых вызовов ИИ подряд (по одному на страницу) — 30 минут с запасом.
     */
    public int $timeout = 1800;

    /**
     * Повторный запуск задвоил бы уже созданные на предыдущей попытке шаблоны/страницы —
     * при ошибке разбираемся вручную, а не ретраим автоматически.
     */
    public int $tries = 1;

    public function __construct(private readonly int $pageImportId){
    }

    public function handle(): void{
        $import = PageImport::find($this->pageImportId);

        if(empty($import)){
            return;
        }

        $import->update(['status' => PageImport::STATUS_PROCESSING]);

        $builder = new PageBuilder();
        $results = [];
        $created = 0;
        $errors = 0;

        $duplicateTitles = $this->extractDuplicateTitles($import, $builder);

        foreach(($import->pages ?? []) as $page){
            $relativePath = $page['file'] ?? null;

            if(empty($relativePath)){
                continue;
            }

            $fullPath = $import->extractedPath().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            try {
                $result = $builder->build($fullPath, $relativePath, $duplicateTitles);
            } catch(\Throwable $e){
                Log::error("PageImport #{$import->id}: ошибка сборки страницы {$relativePath}: ".$e->getMessage());
                $result = ['status' => 'error', 'file' => $relativePath, 'error' => 'exception'];
            }

            if($result['status'] === 'created'){
                $created++;
            }elseif($result['status'] === 'error'){
                $errors++;
            }

            $results[] = $result;

            // Сохраняем прогресс после каждой страницы, а не только в конце — если импорт
            // из многих страниц упадёт на середине, уже собранные страницы не потеряются.
            $import->update(['imported_pages' => $results]);
        }

        $log = $import->log ?? [];
        $log[] = trans('locale.page_import.build_summary', ['created' => $created, 'errors' => $errors]);

        $import->update([
            'status' => $created > 0 ? PageImport::STATUS_REVIEW : PageImport::STATUS_ERROR,
            'log' => $log,
        ]);
    }

    /**
     * Лёгкий предварительный проход по ВСЕМ страницам батча (только чтение `<title>`, без парсинга
     * `<main>`/вызовов ИИ) — нужен, чтобы отличить настоящий уникальный заголовок страницы от
     * общего бренд-плейсхолдера сборки (SPA-донор может отдавать один и тот же статичный `<title>`
     * на КАЖДОЙ странице `dist/`, реальный заголовок там выставляется JS-ом на клиенте уже после
     * гидратации — см. план, «Проверка на втором доноре», находка 3). Заголовок, повторившийся
     * у ≥2 страниц ЭТОГО импорта, считается нерелевантным для всех страниц, где он встретился —
     * `PageBuilder::build()` в этом случае откатывается на имя из пути файла.
     *
     * @return string[] заголовки, требующие отката на titleFromPath()
     */
    private function extractDuplicateTitles(PageImport $import, PageBuilder $builder): array{
        $counts = [];

        foreach(($import->pages ?? []) as $page){
            $relativePath = $page['file'] ?? null;

            if(empty($relativePath)){
                continue;
            }

            $fullPath = $import->extractedPath().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $title = $builder->extractTitle($fullPath);

            if($title === null){
                continue;
            }

            $counts[$title] = ($counts[$title] ?? 0) + 1;
        }

        return array_keys(array_filter($counts, fn($count) => $count > 1));
    }
}