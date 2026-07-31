<?php

namespace Modules\Ai\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class SyncOrderProducts extends Command
{
    protected $signature = 'orders:sync-order-products {--chunk=500 : Количество заказов на чанк}';
    protected $description = 'Разовый бэкфилл: заполняет order_products из JSON-колонки orders.products для существующих заказов';

    public function handle()
    {
        $chunkSize = (int) $this->option('chunk');
        $processed = 0;

        Order::whereNotNull('products')->orderBy('id')->chunk($chunkSize, function ($orders) use (&$processed) {
            foreach ($orders as $order) {
                $order->syncOrderProducts();
                $processed++;
            }
        });

        $this->info("Синхронизировано заказов: {$processed}.");
    }
}