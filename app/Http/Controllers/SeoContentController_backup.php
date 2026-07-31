<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Seo;
use App\Jobs\GenerateSeoContentJob;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SeoContentController extends Controller
{
    public function index()
    {
        // Получаем статистику
        $stats = $this->getStatistics();

        return view('admin.seo-content.index', compact('stats'));
    }

    public function generate(Request $request)
    {
        $limit = $request->input('limit', 50);

        // Инициализируем morphMap для отношений
        Relation::morphMap([
            'Seo' => Seo::class,
            'Categories' => Category::class,
        ]);

        $dispatchedCount = 0;
        $errors = [];

        $this->info("Начинаю поиск страниц для генерации (лимит: {$limit})...");

        // Повторяем логику отбора из сайтмапа
        $categories = Category::where('status', 1)->with('seo', 'attributes.values')->get();

        foreach ($categories as $category) {
            if ($dispatchedCount >= $limit) break;

            // Проверяем наличие товаров в категории через Redis Bitmaps
            $params = ['and', 'temp_count', 'product_visible', 'category_' . $category->id];
            Redis::command('bitop', $params);
            $totalCount = Redis::bitcount('temp_count');

            if (empty($totalCount)) continue;

            // Перебор атрибутов (исключая id 3 и 4, как в коде)
            $attributes = $category->attributes()
                ->where('attributes.id', '!=', 3)
                ->where('attributes.id', '!=', 4)
                ->get();

            foreach ($attributes as $attribute) {
                if ($dispatchedCount >= $limit) break;

                foreach ($attribute->values as $value) {
                    if ($dispatchedCount >= $limit) break;

                    $url = $category->seo->url . '/' . $attribute->slug . '_' . $value->value;

                    // Ищем существующую запись
                    $seo = Seo::where('seotable_type', 'Catalog')
                        ->where('url', $url)
                        ->first();

                    // Проверяем, есть ли товары для этой комбинации
                    $params = ['and', 'count', 'product_visible', 'category_'.$category->id, 'attribute_'.$value->id];
                    Redis::command('bitop', $params);
                    $count = Redis::bitcount('count');

                    // Условие: товары есть, а контента (или самой записи) нет или стоит noindex
                    if ($count > 0 && (!$seo || str_contains($seo->robots, 'noindex'))) {
                        // Если записи нет - создаем, если есть - используем существующую
                        if (!$seo) {
                            $seo = Seo::create([
                                'seotable_id' => $category->id,
                                'seotable_type' => 'Catalog',
                                'url' => $url,
                                'robots' => 'index, follow',
                                'action' => 'showAction',
                            ]);
                        }

                        // Отправляем джобу в очередь
                        try {
                            GenerateSeoContentJob::dispatch($seo->id, [
                                'category'  => $category->name,
                                'attribute' => $attribute->name,
                                'value'     => $value->value,
                            ]);

                            $dispatchedCount++;
                            $this->line("Добавлено в очередь: {$url}");
                        } catch (\Exception $e) {
                            $errors[] = "Ошибка при добавлении URL {$url}: " . $e->getMessage();
                        }
                    }
                }
            }
        }

        // Получаем статистику очереди
        $queueStats = $this->getQueueStatistics();

        return response()->json([
            'success' => true,
            'message' => "Добавлено {$dispatchedCount} задач в очередь",
            'stats' => [
                'total_processed' => $dispatchedCount,
                'errors' => count($errors),
                'queue_stats' => $queueStats
            ],
            'errors' => $errors
        ]);
    }

    public function getProgress()
    {
        $stats = $this->getStatistics();
        $queueStats = $this->getQueueStatistics();

        return response()->json([
            'stats' => $stats,
            'queue_stats' => $queueStats
        ]);
    }

    private function getStatistics()
    {
        // Получаем общую статистику SEO записей
        $totalSeoRecords = Seo::where('seotable_type', 'Catalog')->count();

        // Оптимизированный запрос для подсчета обработанных записей
        $processedSeoRecords = Seo::where('seotable_type', 'Catalog')
            ->whereExists(function($query) {
                $query->select('id')
                    ->from('localization')
                    ->whereRaw('localization.localizable_id = seo.id')
                    ->where('localization.localizable_type', 'Seo')
                    ->whereIn('localization.field', ['meta_title', 'meta_description', 'meta_keywords'])
                    ->whereNotNull('localization.value');
            })
            ->count();

        $pendingRecords = $totalSeoRecords - $processedSeoRecords;

        return [
            'total_seo_records' => $totalSeoRecords,
            'processed_seo_records' => $processedSeoRecords,
            'pending_records' => $pendingRecords,
            'progress_percentage' => $totalSeoRecords > 0 ? round(($processedSeoRecords / $totalSeoRecords) * 100, 2) : 0
        ];
    }

    private function getQueueStatistics()
    {
        try {
            // Получаем статистику очереди Redis
            $queueSize = Redis::connection('default')->llen('queues:default');

            // Получаем количество активных воркеров (если есть)
            $workers = Redis::connection('default')->smembers('workers');
            $activeWorkers = count($workers);

            return [
                'queue_size' => $queueSize,
                'active_workers' => $activeWorkers,
                'status' => $queueSize > 0 ? 'processing' : 'idle'
            ];
        } catch (\Exception $e) {
            return [
                'queue_size' => 0,
                'active_workers' => 0,
                'status' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }
}
