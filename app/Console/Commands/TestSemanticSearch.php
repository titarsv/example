<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;

class TestSemanticSearch extends Command
{
    protected $signature = 'products:semantic-search {query} {--limit=10} {--locale=}';
    protected $description = 'Тестовый прогон семантического поиска по товарам (для проверки качества эмбеддингов из CLI)';

    public function handle()
    {
        // В консоли AppServiceProvider не регистрирует morphMap — без этого $product->name будет пустым
        Relation::morphMap([
            'Products' => Product::class,
            'Categories' => \App\Models\Category::class,
            'Attributes' => \App\Models\Attribute::class,
            'AttributeValues' => \App\Models\AttributeValue::class,
        ]);

        $query = $this->argument('query');
        $limit = (int) $this->option('limit');

        if ($locale = $this->option('locale')) {
            app()->setLocale($locale);
        }

        $this->info("Локаль: " . app()->getLocale());

        $start = microtime(true);
        $results = Product::semanticSearch($query, $limit);
        $elapsed = round((microtime(true) - $start) * 1000);

        if ($results->isEmpty()) {
            $this->warn('Ничего не найдено. Возможные причины: нет проиндексированных товаров для этой локали, либо не удалось построить эмбеддинг запроса (проверьте, что Ollama/Gemini доступны).');
            return;
        }

        $this->table(
            ['ID', 'Название', 'Цена'],
            $results->map(fn($p) => [$p->id, $p->name, $p->price])->toArray()
        );

        $this->info("Найдено: {$results->count()}. Время: {$elapsed} мс.");
    }
}