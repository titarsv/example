<?php

namespace App\Jobs;

use App\Models\Localization;
use App\Services\AiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class TranslateLocalizationBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Увеличиваем попытки, так как теперь есть логика Fallback
    public $tries = 10;
    public $backoff = 300;

    protected array $items;
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
        Log::info("[TranslateJob] Старт батча. Записей: " . count($this->items));

        Redis::throttle('ai-translate-limiter')
            ->allow(15)
            ->every(60)
            ->block(0)
            ->then(function () use ($ai) {

                $uniqueStrings = array_unique(array_values($this->items));
                $toTranslate = array_combine($uniqueStrings, $uniqueStrings);

                try {
                    // Первый проход стандартной моделью
                    $translatedMap = $ai->translateBatch($toTranslate, $this->targetLang);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'exceeded your current quota')) {
                        Log::warning("[TranslateJob] Квота исчерпана. Спим час.");
                        return $this->release(3600);
                    }
                    throw $e;
                }

                if (!$translatedMap) {
                    Log::error("[TranslateJob] AI вернул пустой ответ.");
                    return;
                }

                $updatedCount = 0;

                foreach ($this->items as $id => $originalValue) {
                    $result = $translatedMap[$originalValue] ?? null;

                    $translatedValue = null;
                    $isVerified = false;

                    if (is_array($result)) {
                        $translatedValue = $result['text'] ?? null;
                        $isVerified = ($result['status'] ?? '') === 'verified';
                    } else {
                        // Для совместимости с другими провайдерами или если модель вернула строку
                        $translatedValue = $result;
                    }

                    // ПРОВЕРКА: Если модель вернула тот же текст (и это не число/артикул и не подтверждено моделью)
                    if (!$isVerified && !$this->isTranslationValid($originalValue, $translatedValue)) {
                        Log::info("[TranslateJob] Fallback: Строка не изменилась. Пробуем умную модель для: " . mb_substr($originalValue, 0, 50));

                        // Попытка №2: Используем "умную" одиночную модель
                        $translatedValue = $ai->translateSingleSmart($originalValue, $this->targetLang);
                    }

                    if ($translatedValue) {
                        $updatedCount += $this->applyTranslationToDatabase($id, $originalValue, $translatedValue);
                    }
                }

                Log::info("[TranslateJob] Успешно сохранено записей: {$updatedCount}");

            }, function () {
                return $this->release(60);
            });
    }

    /**
     * Валидация: считаем перевод успешным, если он не равен оригиналу
     * или если оригинал сам по себе является числом/артикулом.
     */
    private function isTranslationValid($original, $translated): bool
    {
        if (is_null($translated)) return false;

        $trimmedOriginal = trim($original);
        $trimmedTranslated = trim($translated);

        // Если текст изменился — всё ок
        if ($trimmedOriginal !== $trimmedTranslated) return true;

        // Если текст не изменился, но это число — это тоже ок
        if (is_numeric($trimmedOriginal)) return true;

        // В остальных случаях (строка осталась прежней) — считаем, что модель "сфилонила"
        return false;
    }

    /**
     * Сохранение в базу с массовым обновлением дубликатов
     */
    private function applyTranslationToDatabase($id, $originalValue, $translatedValue): int
    {
        $count = 0;
        $record = Localization::find($id);
        Log::warning($id.':'.$originalValue.':'.$translatedValue);
        if ($record) {
            // Массово обновляем все идентичные строки в исходном языке
            $massUpdated = Localization::where('value', $originalValue)
                ->where('language', $this->sourceLang)
                ->get();

            foreach ($massUpdated as $subRecord) {
                Localization::updateOrCreate([
                    'localizable_type' => $subRecord->localizable_type,
                    'localizable_id'   => $subRecord->localizable_id,
                    'field'            => $subRecord->field,
                    'language'         => $this->targetLang,
                ], [
                    'value' => $translatedValue
                ]);
                $count++;
            }
        }
        return $count;
    }
}
