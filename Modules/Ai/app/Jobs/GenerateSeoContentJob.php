<?php

namespace Modules\Ai\Jobs;

use App\Models\Category;
use App\Models\Seo;
use App\Models\Localization;
use Modules\Ai\Services\AiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSeoContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $seoId;
    protected $context;

    /**
     * @param int $seoId ID записи из таблицы Seo
     * @param array $context ['category' => '...', 'attribute' => '...', 'value' => '...']
     */
    public function __construct(int $seoId, array $context)
    {
        $this->seoId = $seoId;
        $this->context = $context;
    }

    public function handle(AiServiceInterface $ai)
    {
        Relation::morphMap([
            'Seo' => Seo::class,
            'Categories' => Category::class,
        ]);
        $seo = Seo::find($this->seoId);

        if (!$seo) {
            Log::error("GenerateSeoContentJob: Запись Seo с ID {$this->seoId} не найдена.");
            return;
        }

        // Вызываем метод сервиса
        $aiData = $ai->generateSeoFiltersContent($this->context);

        if (!$aiData) {
            Log::error("GenerateSeoContentJob: Gemini не вернула данные для SEO ID {$this->seoId}");
            return;
        }

        try {
            // Проходим по языкам, которые вернула нейронка (они соответствуют config('app.locales'))
            foreach ($aiData as $lang => $fields) {
                foreach ($fields as $field => $value) {
                    // Используем updateOrCreate для таблицы локализаций
                    Localization::updateOrCreate([
                        'localizable_id'   => $seo->id,
                        'localizable_type' => 'Seo',
                        'language'         => $lang,
                        'field'            => $field,
                    ], [
                        'value'            => $value
                    ]);
                }
            }

            // Когда все локализации записаны, открываем страницу для индексации
            $seo->update([
                'robots' => 'index, follow'
            ]);

            Log::info("GenerateSeoContentJob: SEO контент успешно сгенерирован для URL: {$seo->url}");

        } catch (\Exception $e) {
            Log::error("GenerateSeoContentJob Error: " . $e->getMessage());
        }
    }
}
