<?php

namespace Modules\Ai\Jobs;

use App\Models\File;
use App\Models\Localization;
use Modules\Ai\Services\AiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class GenerateProductAltText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5; // Увеличим до 5, так как лимиты API — частая причина повторов
    public $backoff = 60;

    protected File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function handle(AiServiceInterface $ai)
    {
        // Ограничитель на 10 запросов в 60 секунд
        Redis::throttle('ai-api-limiter')
            ->allow(10)
            ->every(60)
            ->block(0)
            ->then(function () use ($ai) {
                if (!$this->file->path || $this->file->alt) {
                    return;
                }

                $absolutePath = public_path($this->file->path);

                if (!file_exists($absolutePath)) {
                    Log::warning("Файл не найден: {$absolutePath}");
                    $this->file->update(['alt' => $this->file->title]);
                    return;
                }

                $metadata = $ai->generateImageMetadata($absolutePath);

                if ($metadata) {
                    $data = $this->file->data;
                    foreach ($metadata as $lang => $fields) {
                        if($lang == app()->getLocale()){
                            $this->file->alt = $fields['alt'];
                            $this->file->title = $fields['title'];
                            $this->file->description = $fields['description'];
                            $data['alt'] = $fields['alt'];
                            $data['title'] = $fields['title'];
                            $data['description'] = $fields['description'];
                        }
                        $data['alt_'.$lang] = $fields['alt'];
                        $data['title_'.$lang] = $fields['title'];
                        $data['description_'.$lang] = $fields['description'];
                        foreach ($fields as $fieldName => $value) {
                            Localization::updateOrCreate([
                                'localizable_type' => 'Files',
                                'localizable_id'   => $this->file->id,
                                'field'            => $fieldName,
                                'language'         => $lang,
                            ], [
                                'value' => $value
                            ]);
                        }
                    }
                    $this->file->data = json_encode($data, JSON_UNESCAPED_UNICODE);
                    $this->file->save();
                }
            }, function () {
                // Если не вписались в лимит, возвращаем задачу в очередь через 60 сек
                return $this->release(60);
            });
    }
}
