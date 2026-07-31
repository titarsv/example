<?php

namespace Modules\Ai\Console\Commands;

use App\Models\Localization;
use Modules\Ai\Jobs\TranslateLocalizationBatch;
use Modules\Ai\Jobs\TranslateJsonJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BatchGenerateTranslates extends Command
{
    protected $signature = 'gemini:translate
                        {--to=ru : Целевой язык (код)}
                        {--limit=500 : Лимит уникальных записей}
                        {--queue=default : Очередь}';

    protected $description = 'Анализирует контент и распределяет задачи на перевод';

    public function handle()
    {
        $targetLang = $this->option('to');
        $sourceLang = 'ua'; // Исходный язык
        $limit = (int) $this->option('limit');
        $queueName = $this->option('queue');

        $this->info("Анализ уникальных строк для перевода на: {$targetLang}...");

        // Исправленный запрос: находим строки, которые НЕ переведены на целевой язык
        $records = Localization::where('language', $sourceLang)
            ->where('value', '!=', '')
            ->whereNotExists(function ($query) use ($targetLang) {
                $query->select(DB::raw(1))
                    ->from('localization as secondary')
                    ->whereRaw('secondary.localizable_id = localization.localizable_id')
                    ->whereRaw('secondary.localizable_type = localization.localizable_type')
                    ->whereRaw('secondary.field = localization.field')
                    ->where('secondary.language', $targetLang);
            })
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            $this->warn("Все строки уже переведены.");
            return;
        }

        $this->info("Найдено уникальных строк: " . $records->count());

        $currentBatch = [];
        $currentBatchLength = 0;
        $maxLength = 1500;
        $instantCount = 0;

        foreach ($records as $record) {
            $val = trim($record->value);

            // 1. Мгновенная обработка ЧИСЕЛ (не тратим квоту API)
            if (is_numeric($val)) {
                $this->saveInstantTranslation($record, $targetLang, $val);
                $instantCount++;
                continue;
            }

            // 2. Обработка JSON (отдельный поток)
            if ($this->isJson($val)) {
                TranslateJsonJob::dispatch([$record->id => $val], $targetLang, $sourceLang)
                    ->onQueue($queueName);
                continue;
            }

            $stringLength = mb_strlen($val);

            // 3. Если строка гигантская — отправляем её одну
            if ($stringLength > $maxLength) {
                if (!empty($currentBatch)) {
                    $this->dispatchBatch($currentBatch, $targetLang, $sourceLang, $queueName);
                    $currentBatch = [];
                    $currentBatchLength = 0;
                }
                $this->dispatchBatch([$record->id => $val], $targetLang, $sourceLang, $queueName);
                continue;
            }

            // 4. Упаковка в батч по размеру
            if (($currentBatchLength + $stringLength) > $maxLength) {
                $this->dispatchBatch($currentBatch, $targetLang, $sourceLang, $queueName);
                $currentBatch = [];
                $currentBatchLength = 0;
            }

            $currentBatch[$record->id] = $val;
            $currentBatchLength += $stringLength;
        }

        // Хвост батча
        if (!empty($currentBatch)) {
            $this->dispatchBatch($currentBatch, $targetLang, $sourceLang, $queueName);
        }

        $this->info("Обработка завершена:");
        $this->line("- Сохранено чисел мгновенно: {$instantCount}");
        $this->line("- Задачи на перевод добавлены в очередь: {$queueName}");
    }

    /**
     * Сохранение без участия ИИ (для чисел и артикулов)
     */
    private function saveInstantTranslation($record, $targetLang, $value)
    {
        Localization::updateOrCreate([
            'localizable_type' => $record->localizable_type,
            'localizable_id'   => $record->localizable_id,
            'field'            => $record->field,
            'language'         => $targetLang,
        ], [
            'value' => $value
        ]);
    }

    private function dispatchBatch($items, $targetLang, $sourceLang, $queueName)
    {
        TranslateLocalizationBatch::dispatch($items, $targetLang, $sourceLang)->onQueue($queueName);
    }

    protected function isJson($string)
    {
        if (is_numeric($string)) return false;
        $data = json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE) && (is_object($data) || is_array($data));
    }
}
