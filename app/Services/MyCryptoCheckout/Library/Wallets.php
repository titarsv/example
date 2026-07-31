<?php
namespace App\Services\MyCryptoCheckout\Library;

use Illuminate\Support\Collection;

class Wallets extends Collection
{
    /**
     * Выбирает кошелек, который дольше всего не использовался.
     */
    public function get_dustiest_wallet($currency_id)
    {
        // 1. Фильтруем кошельки по нужной валюте
        $filtered = $this->filter(function($wallet) use ($currency_id) {
            return $wallet->currency_id === $currency_id;
        });

        if ($filtered->isEmpty()) {
            return null;
        }

        // 2. Сортируем по времени последнего использования (last_used)
        // Кошельки без метки времени (null) пойдут в начало очереди
        return $filtered->sortBy('last_used')->first();
    }
}
