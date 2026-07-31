<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Jobs\GenerateProductAltText;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;

class BatchGenerateMetadata extends Command
{
    protected $signature = 'images:generate-metadata {--limit=100 : Количество изображений для обработки}';
    protected $description = 'Добавляет задачи по генерации Alt-текстов в очередь';

    public function handle()
    {
        Relation::morphMap([
            'Files' => File::class,
        ]);

        $limit = $this->option('limit');

        $files = File::where('type', 'image')->whereNull('alt')->whereNotNull('path')->limit($limit)->get();

        if ($files->isEmpty()) {
            $this->info('Нет изображений для обработки.');
            return;
        }

        $this->info("Добавляем {$files->count()} задач в очередь...");

        foreach ($files as $file) {
            // Отправляем в очередь 'gemini-api', чтобы контролировать скорость
//            GenerateProductAltText::dispatch($file)->onQueue('gemini-api');
            GenerateProductAltText::dispatch($file);
        }

        $this->info('Готово. Убедитесь, что воркер запущен.');
    }
}
