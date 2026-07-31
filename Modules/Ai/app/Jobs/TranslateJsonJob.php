<?php

namespace Modules\Ai\Jobs;

use App\Models\Localization;
use Modules\Ai\Services\AiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class TranslateJsonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 120;

    protected array $items; // Формат: [id => "original text"]
    protected string $targetLang;
    protected string $sourceLang;

    public function __construct(array $items, string $targetLang, string $sourceLang = null)
    {
        $this->items = $items;
        $this->targetLang = $targetLang;
        $this->sourceLang = $sourceLang ?? app()->getLocale();
    }

    public function handle(AiServiceInterface $ai)
    {
        Redis::throttle('ai-json-limiter')->allow(5)->every(60)->block(0)->then(function () use ($ai) {
            foreach ($this->items as $id => $jsonString) {
                $translatedJson = $ai->translateJsonString($jsonString, $this->targetLang);

                if ($translatedJson) {
                    $record = Localization::find($id);
                    if ($record) {
                        Localization::updateOrCreate([
                            'localizable_type' => $record->localizable_type,
                            'localizable_id'   => $record->localizable_id,
                            'field'            => $record->field,
                            'language'         => $this->targetLang,
                        ], [
                            'value' => $translatedJson
                        ]);

                        // Массово обновляем такие же JSON-строки, если они есть
                        Localization::where('value', $jsonString)
                            ->where('language', $this->sourceLang)
                            ->update(['value' => $translatedJson]); // Упростил для примера
                    }
                }
            }
        });
    }
}
