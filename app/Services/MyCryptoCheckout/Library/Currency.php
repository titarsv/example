<?php
namespace App\Services\MyCryptoCheckout\Library;

class Currency
{
    public $id;
    public $data; // Сюда сохраним объект из вашего дампа (decimal_precision и т.д.)
    protected $api;

    public function __construct($id, $data, $api)
    {
        $this->id = $id;
        $this->data = $data;
        $this->api = $api;
    }

    public function get_id() { return $this->id; }

    public function get_name() { return $this->data->name ?? $this->id; }

    public function get_decimal_precision() {
        return $this->data->decimal_precision ?? 8;
    }

    /**
     * Конвертация суммы из фиата в эту крипту
     * На основе вашего дампа: [virtual_exchange_rates][rates][BTC]
     */
    public function convert($fiat_currency, $amount)
    {
        $account_data = $this->api->account()->data;

        // В вашем дампе BTC к USD находится в virtual_exchange_rates
        // Например: [BTC] => 1.1496762884182E-5 (это цена 1 USD в BTC)
        $rate = $account_data->virtual_exchange_rates->rates->{$this->id} ?? null;

        if (!$rate) {
            // Иногда бывает в physical_exchange_rates
            $rate = $account_data->physical_exchange_rates->rates->{$this->id} ?? null;
        }

        if (!$rate) return 0;

        return $amount * $rate;
    }

    /**
     * Находит ближайшую свободную сумму для этой валюты.
     */
    public function find_next_available_amount($amount)
    {
        $precision = $this->get_decimal_precision();
        $account_data = $this->api->account()->data;
        $occupied_amounts = [];

        // Проверяем существование и тип перед работой
        if (isset($account_data->payment_amounts->{$this->id})) {
            $data = $account_data->payment_amounts->{$this->id};
            // Учитываем, что это может быть и объект, и массив
            $occupied_amounts = array_keys((array)$data);
        }

        // Ищем свободную сумму, прибавляя минимально возможный шаг
        // Например, если занято 0.001, пробуем 0.00100001
        $step = 1 / pow(10, $precision);

        while (in_array((string)$amount, $occupied_amounts)) {
            $amount = $amount + $step;
            // Округляем до текущей точности валюты, чтобы избежать ошибок плавающей точки
            $amount = round($amount, $precision);
        }

        return $amount;
    }
}
