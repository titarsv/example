<?php

namespace Modules\Ai\Console\Commands;

use Modules\Ai\Jobs\GenerateProductEmbeddingJob;
use App\Models\Product;
use Modules\Ai\Models\ProductEmbedding;
use Illuminate\Console\Command;

class BuildProductEmbeddings extends Command
{
    protected $signature = 'embeddings:build-products
        {--limit= : Ограничить количество товаров}
        {--only-missing : Ставить в очередь только товары без эмбеддинга (пропускать уже проиндексированные)}';

    protected $description = 'Ставит в очередь генерацию эмбеддингов товаров для семантического поиска';

    public function handle()
    {
        $locales = config('app.locales', [config('app.locale')]);
        $onlyMissing = (bool) $this->option('only-missing');

        $query = Product::where('visible', 1)->orderBy('id');
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $products = $query->get(['id']);

        if ($products->isEmpty()) {
            $this->info('Нет товаров для обработки.');
            return;
        }

        $existing = [];
        if ($onlyMissing) {
            $existing = ProductEmbedding::whereIn('product_id', $products->pluck('id'))
                ->get(['product_id', 'locale'])
                ->map(fn($e) => $e->product_id . '_' . $e->locale)
                ->flip()
                ->toArray();
        }

        $dispatched = 0;
        foreach ($products as $product) {
            foreach ($locales as $locale) {
                if ($onlyMissing && isset($existing[$product->id . '_' . $locale])) {
                    continue;
                }
                GenerateProductEmbeddingJob::dispatch($product->id, $locale);
                $dispatched++;
            }
        }

        $this->info("Товаров: {$products->count()}. Задач поставлено в очередь: {$dispatched}.");
        $this->info('Убедитесь, что запущен воркер очереди: php artisan queue:work');
    }
}