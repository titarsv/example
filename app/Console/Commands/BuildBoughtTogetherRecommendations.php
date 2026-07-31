<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BuildBoughtTogetherRecommendations extends Command
{
    protected $signature = 'recommendations:bought-together';
    protected $description = 'Пересчитывает пары "часто покупают вместе" по подтверждённым заказам (order_products) и сохраняет их в Redis';

    // Заказ считается сигналом для рекомендаций, если он не "Новый" (1) и не "Отменён" (7)
    protected array $confirmedStatuses = [2, 3, 4, 5, 6];

    public function handle()
    {
        $this->info('Очищаем старые данные...');
        $this->clearExistingKeys();

        // Один товар может встречаться в заказе несколькими строками (разные вариации) —
        // убираем дубли по (order_id, product_id) перед построением пар, иначе такой заказ
        // задвоит счётчик совпадения с остальными товарами в нём.
        $distinctOrderProducts = function () {
            return DB::table('order_products')->select('order_id', 'product_id')->distinct();
        };

        $pairs = DB::query()
            ->fromSub($distinctOrderProducts(), 'a')
            ->joinSub($distinctOrderProducts(), 'b', function ($join) {
                $join->on('a.order_id', '=', 'b.order_id')
                    ->on('a.product_id', '<', 'b.product_id');
            })
            ->join('orders', 'orders.id', '=', 'a.order_id')
            ->whereIn('orders.status_id', $this->confirmedStatuses)
            ->selectRaw('a.product_id as product_a, b.product_id as product_b, count(*) as cnt')
            ->groupBy('a.product_id', 'b.product_id')
            ->get();

        $this->info("Найдено пар товаров: {$pairs->count()}.");

        $this->writePairsToRedis($pairs);

        $this->info('Готово.');
    }

    protected function writePairsToRedis($pairs): void
    {
        if ($pairs->isEmpty()) {
            return;
        }

        $bar = $this->output->createProgressBar($pairs->count());

        foreach ($pairs as $pair) {
            Redis::command('zadd', ["bought_with_{$pair->product_a}", $pair->cnt, $pair->product_b]);
            Redis::command('zadd', ["bought_with_{$pair->product_b}", $pair->cnt, $pair->product_a]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function clearExistingKeys(): void
    {
        $keys = Redis::command('keys', ['bought_with_*']);
        if (!empty($keys)) {
            foreach ($keys as $key) {
                Redis::command('del', [$key]);
            }
        }
    }
}
