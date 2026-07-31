<?php

namespace Modules\Ai\Jobs;

use App\Models\Product;
use Modules\Ai\Models\ProductEmbedding;
use Modules\Ai\Services\AiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class GenerateProductEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $productId;
    protected string $locale;

    public function __construct(int $productId, string $locale)
    {
        $this->productId = $productId;
        $this->locale = $locale;
    }

    public function handle(AiServiceInterface $ai)
    {
        // AppServiceProvider не регистрирует morphMap в консольном контексте (queue worker),
        // поэтому связи localization/attributes нужно резолвить локально — как и в других джобах проекта.
        Relation::morphMap([
            'Products' => Product::class,
            'Categories' => \App\Models\Category::class,
            'Attributes' => \App\Models\Attribute::class,
            'AttributeValues' => \App\Models\AttributeValue::class,
        ]);

        $product = Product::find($this->productId);
        if (empty($product)) {
            return;
        }

        // Название/описание товара читаются через localize() по текущей локали приложения
        $originalLocale = App::getLocale();
        App::setLocale($this->locale);

        try {
            $hasName = trim((string) $product->name) !== '';
            $text = $product->getEmbeddingText();
        } finally {
            App::setLocale($originalLocale);
        }

        // Товар не переведён на эту локаль (нет названия) — не индексируем. Иначе в текст
        // попадает только категория ("Apple" и т.п.), эмбеддинг из такого огрызка получается
        // "усреднённым" и даёт непредсказуемо высокое сходство со случайными запросами,
        // засоряя выдачу семантического поиска нерелевантными товарами.
        if (!$hasName || trim($text) === '') {
            return;
        }

        $vector = $ai->embed($text);
        if (empty($vector)) {
            Log::error("Не удалось построить эмбеддинг для товара {$this->productId} ({$this->locale})");
            return;
        }

        $model = config('services.ai_provider') === 'ollama'
            ? config('services.ollama.embedding_model')
            : config('services.gemini.embedding_model');

        ProductEmbedding::updateOrCreate(
            ['product_id' => $this->productId, 'locale' => $this->locale],
            [
                'model' => $model,
                'dimensions' => count($vector),
                'vector' => json_encode($vector),
            ]
        );
    }
}