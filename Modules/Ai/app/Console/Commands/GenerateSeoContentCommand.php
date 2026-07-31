<?php

namespace Modules\Ai\Console\Commands;

use App\Models\Category;
use App\Models\Seo;
use Modules\Ai\Jobs\GenerateSeoContentJob;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Redis;

class GenerateSeoContentCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'seo:generate-content {--limit=50 : Сколько страниц отправить в очередь за один раз}';

    /**
     * @var string
     */
    protected $description = 'Находит пустые SEO-страницы фильтров и отправляет их в очередь на генерацию через Gemini';

    public function handle()
    {
        Relation::morphMap([
            'Seo' => Seo::class,
            'Categories' => Category::class,
        ]);

        $limit = (int) $this->option('limit');
        $dispatchedCount = 0;

        $this->info("Начинаю поиск страниц для генерации (лимит: {$limit})...");

        // Повторяем твою логику отбора из сайтмапа
        $categories = Category::where('status', 1)->with('seo', 'attributes.values')->get();

        foreach ($categories as $category) {
            if ($dispatchedCount >= $limit) break;

            // Проверяем наличие товаров в категории через Redis Bitmaps
            $params = ['and', 'temp_count', 'product_visible', 'category_' . $category->id];
            Redis::command('bitop', $params);
            $totalCount = Redis::bitcount('temp_count');

            if (empty($totalCount)) continue;

            // Перебор атрибутов (исключая id 3 и 4, как в твоем коде)
            $attributes = $category->attributes()
                ->where('attributes.id', '!=', 3)
                ->where('attributes.id', '!=', 4)
                ->get();

            foreach ($attributes as $attribute) {
                if ($dispatchedCount >= $limit) break;

                foreach ($attribute->values as $value) {
                    if ($dispatchedCount >= $limit) break;

                    $url = $category->seo->url . '/' . $attribute->slug . '_' . $value->value;

                    // Ищем существующую запись
                    $seo = Seo::where('seotable_type', 'Catalog')
                        ->where('url', $url)
                        ->first();

                    // Проверяем, есть ли товары для этой комбинации
                    $params = ['and', 'count', 'product_visible', 'category_'.$category->id, 'attribute_'.$value->id];
                    Redis::command('bitop', $params);
                    $count = Redis::bitcount('count');

                    // Условие: товары есть, а контента (или самой записи) нет или стоит noindex
                    if ($count > 0 && (!$seo || str_contains($seo->robots, 'noindex'))) {

                        // Если записи нет - создаем, если есть - используем существующую
                        if (!$seo) {
                            $seo = Seo::create([
                                'seotable_id' => $category->id,
                                'seotable_type' => 'Catalog',
                                'url' => $url,
                                'robots' => 'index, follow',
                                'action' => 'showAction',
                            ]);
                        }

                        // Отправляем джобу в очередь
                        GenerateSeoContentJob::dispatch($seo->id, [
                            'category'  => $category->name,
                            'attribute' => $attribute->name,
                            'value'     => $value->value,
                        ]);

                        $dispatchedCount++;
                        $this->line("Добавлено в очередь: {$url}");
                    }
                }
            }
        }

        $this->info("Готово! В очередь добавлено {$dispatchedCount} задач.");
    }
}
