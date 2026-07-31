<?php

namespace App\Services\MyCryptoCheckout;

use mycryptocheckout\api\v2\Account as BaseAccount;
use mycryptocheckout\api\v2\Client_Account_Data;
use mycryptocheckout\api\v2\Exception;

class LaravelAccount extends BaseAccount
{
    /**
     * Ключ для временного хранения ключа верификации.
     */
    public static $account_retrieve_key = 'account_retrieve_key';

    /**
     * Генерируем данные о нашем сайте для отправки в MCC.
     */
    public function generate_client_account_data()
    {
        // Используем правильный класс данных из пространства имен SDK
        $client_account_data = new \mycryptocheckout\api\v2\Client_Account_Data();

        // 1. Вместо MyCryptoCheckout()->get_client_url()
        // используем метод нашего LaravelAPI через связь $this->api()
        $client_account_data->domain = base64_encode($this->api()->get_client_url());

        // 2. Вместо константы плагина WP используем версию SDK
        // или произвольную строку версии вашего приложения
        $client_account_data->plugin_version = '1.0.0-laravel';

        return $client_account_data;
    }

    /**
     * Проверка доступности оплаты.
     */
    public function is_available_for_payment()
    {
        // Вызываем родительский метод (он проверяет наличие domain_key)
        parent::is_available_for_payment();

        // Вместо MyCryptoCheckout()->wallets() используем настройки Laravel
        $wallets = config('site.modules.shop.modules.mcc.wallets');

        if (empty($wallets)) {
            throw new Exception('Криптовалютные кошельки не настроены в config/services.php');
        }

        return true;
    }

    /**
     * Проверка ключа ретрива (используется при активации аккаунта).
     */
    public function is_retrieve_key_valid($retrieve_key)
    {
        // Используем наш LaravelAPI (через метод api()), который уже умеет работать с нашей БД
        $stored_value = $this->api()->get_data(static::$account_retrieve_key);

        if (!$stored_value) {
            throw new Exception('Ключ верификации не найден в БД. Ожидание ответа от сервера не активно.');
        }

        return ($stored_value == $retrieve_key);
    }

    /**
     * Отправка данных аккаунта на сервер.
     */
    public function send_client_account_data(\mycryptocheckout\api\v2\Client_Account_Data $client_account_data)
    {
        // Вместо MyCryptoCheckout()->api() используем встроенный метод $this->api()
        // Он обращается к вашему классу LaravelAPI
        $result = $this->api()->send_post('account/retrieve', $client_account_data);

        if (!$result) {
            throw new \mycryptocheckout\api\v2\Exception('No valid answer from the API server.');
        }

        return $result;
    }

    /**
     * Сохранение временного ключа для привязки.
     */
    public function set_retrieve_key($retrieve_key)
    {
        // Сохраняем в нашу таблицу mcc_data через API
        $this->api()->save_data(static::$account_retrieve_key, $retrieve_key);
    }

    public function load_data()
    {
        $this->data = (object)[];

        $data = $this->api()->get_data( static::$account_data_site_option_key );
        // Try gunzipping?
        if(is_string($data)){
            $loaded_data = static::decompress( $data );
            if ( ! $loaded_data )
                $loaded_data = (object)[];
        }elseif(is_object($data)){
            $loaded_data = $data;
        }

        if (!isset($loaded_data))
            $loaded_data = (object)[];

        $this->data = $loaded_data;
    }
}
