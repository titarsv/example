<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Jobs\GenerateProductAltText;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;

class MetadataController extends Controller
{
    public function index()
    {
        // Получаем статистику
        $stats = $this->getStatistics();
        
        return view('admin.metadata.index', compact('stats'));
    }

    public function generate(Request $request)
    {
        $limit = $request->input('limit', 100);
        
        // Инициализируем morphMap для отношений
        Relation::morphMap([
            'Files' => File::class,
        ]);

        // Получаем изображения без alt текста
        $files = File::where('type', 'image')
            ->whereNull('alt')
            ->whereNotNull('path')
            ->limit($limit)
            ->get();

        if ($files->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет изображений для обработки.'
            ]);
        }

        $totalFiles = $files->count();
        $processedCount = 0;
        $errors = [];

        // Добавляем задачи в очередь
        foreach ($files as $file) {
            try {
                GenerateProductAltText::dispatch($file);
                $processedCount++;
            } catch (\Exception $e) {
                $errors[] = "Ошибка при добавлении файла ID {$file->id}: " . $e->getMessage();
            }
        }

        // Получаем статистику очереди
        $queueStats = $this->getQueueStatistics();

        return response()->json([
            'success' => true,
            'message' => "Добавлено {$processedCount} задач в очередь",
            'stats' => [
                'total_files' => $totalFiles,
                'processed' => $processedCount,
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
        $totalImages = File::where('type', 'image')->count();
        $processedImages = File::where('type', 'image')->whereNotNull('alt')->count();
        $pendingImages = $totalImages - $processedImages;
        
        return [
            'total_images' => $totalImages,
            'processed_images' => $processedImages,
            'pending_images' => $pendingImages,
            'progress_percentage' => $totalImages > 0 ? round(($processedImages / $totalImages) * 100, 2) : 0
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
