<?php
namespace App\Services\MyCryptoCheckout\Library;

use Illuminate\Support\Collection;

class Currencies extends Collection
{
    public function add($currency)
    {
        $this->put($currency->get_id(), $currency);
        return $this;
    }

    // Метод get уже есть в родителе Laravel Collection
}
