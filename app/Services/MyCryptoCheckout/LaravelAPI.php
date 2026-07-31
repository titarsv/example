<?php

namespace App\Services\MyCryptoCheckout;

use App\Services\MyCryptoCheckout\Library\Currencies;
use App\Services\MyCryptoCheckout\Library\Currency;
use App\Services\MyCryptoCheckout\Library\Wallets;
use mycryptocheckout\api\v2\API;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use mycryptocheckout\api\v2\Exception;

class LaravelAPI extends API
{
    protected $settings;

    public function __construct()
    {
        if (!defined('mycryptocheckout\api\v2\AUTH_SALT')) {
            define('mycryptocheckout\api\v2\AUTH_SALT', env('APP_KEY', 'some-fallback-salt'));
        }
        $this->settings = new Setting();
    }


    /**
     * Возвращает URL вашего сайта.
     * MCC использует это для идентификации вашего аккаунта.
     */
    public function get_client_url()
    {
        return config('app.url') . '/api/mcc_callback';
    }

    /**
     * Сохранение данных (например, данных аккаунта MCC).
     * Используем БД или Cache (лучше БД, если данные постоянные).
     */
    public function save_data($key, $data)
    {
        $this->settings->update_setting('mcc_' . $key, $data);
    }

    public function get_data($key, $default = false)
    {
        $data = $this->settings->get_setting('mcc_' . $key);

        return !empty($data) ? $data : $default;
    }

    public function delete_data( $key )
    {
        $this->settings->update_setting('mcc_' . $key, null);
    }

    /**
     * Реализация POST запроса через Laravel Http Client
     */
    public function send_post($url, $data)
    {
        $response = Http::asJson()
            ->post(static::get_api_url() . $url, $data);

        if ($response->failed()) {
            throw new \Exception('MCC API Post Error: ' . $response->body());
        }

        return $response->object();
    }

    /**
     * Реализация GET запроса
     */
    public function send_get($url)
    {
        $response = Http::get(static::get_api_url() . $url);

        if ($response->failed()) {
            throw new \Exception('MCC API Get Error: ' . $response->body());
        }

        return $response->object();
    }

    // Переопределяем метод вывода (опционально для Laravel)
    public function debug($string)
    {
        \Log::debug('MyCryptoCheckout: ' . $string);
    }

    /**
     * Переопределяем создание компонента аккаунта на нашу Laravel-версию.
     */
    public function new_account()
    {
        return new \App\Services\MyCryptoCheckout\LaravelAccount($this);
    }

    public function new_payments()
    {
        return new \App\Services\MyCryptoCheckout\LaravelPayments($this);
    }

    public function process_messages( $json )
    {
        // This must be an array.
        if ( ! is_array( $json->messages ) )
            \Log::debug("JSON does not contain a messages array.");

        // First check for a retrieve_account message.
        foreach( $json->messages as $message )
        {
            if ( $message->type != 'retrieve_account' )
                continue;
            if ( ! $this->account()->is_retrieve_key_valid( $message->retrieve_key ) )
                throw new Exception( sprintf( 'Retrieve keys do not match: %s.', $message->retrieve_key ) );
            // Everything looks good to go.
            $new_account_data = (object) (array) $message->account;
            \Log::debug("Setting new account data: ".strlen( json_encode( $new_account_data ) )." bytes plain, ".strlen( $this->account()->compress( $new_account_data ) )." bytes compressed");
            $this->account()->set_data( $new_account_data )
                ->save();
        }

        $account = $this->account();
        if ( ! $account->is_valid() )
            \Log::debug("No account data found. Ignoring messages.");

        // Check that the domain key matches ours.
        if ( $account->get_domain_key() != $json->mycryptocheckout )
            \Log::debug("Invalid domain key. Received " . $json->mycryptocheckout);

        // Handle the messages, one by one.
        foreach( $json->messages as $message )
        {
            // complete_payment is more logical, but the server can't be updated.
            if ( $message->type == 'payment_complete' )
                $message->type = 'complete_payment';

            \Log::debug("Processing a " . $message->type . " message.");

            if ( isset( $message->payment ) )
            {
                // Create a payment object that is used to transfer the completion / cancellation data from the server to whoever handles the message.
                $payment = $this->payments()->create_new( $message->payment );
                $payment->set_id( $message->payment->payment_id );		// This is already set during create_new(), but we want to be extra clear.
                if ( isset( $message->payment->transaction_id ) )		// Completions require this.
                    $payment->set_transaction_id( $message->payment->transaction_id );	// This is already set during create_new(), but we want to be extra clear.
            }

            switch( $message->type )
            {
                case 'retrieve_account':
                    // Already handled above.
                    break;
                case 'cancel_payment':
                    // Mark the local payment as canceled.
                    $this->payments()->cancel_local( $payment );
                    break;
                case 'complete_payment':
                    // Mark the local payment as complete.
                    $this->payments()->complete_local( $payment );
                case 'test_communication':
                    return response()->json(['result' => 'ok', 'message' => date('Y-m-d H:i:s' )]);
                case 'update_account':
                    // Save our new account data.
                    $new_account_data = (object) (array) $message->account;
                    $this->save_data( 'account_data', json_encode( $new_account_data ) );
                    break;
                default:
                    throw new Exception( sprintf( 'Unknown message type: %s', $message->type ) );
            }
        }
    }

    public function maybe_process_messages()
    {
        // 1. Используем фасад Request для получения данных
        $request = request();

        // 2. Проверяем Content-Type (в Laravel это делается изящнее)
        if (!$request->isJson()) {
            return;
        }

        // 3. Получаем JSON-объект
        $json = $request->json()->all();

        // Превращаем в объект (SDK ожидает stdClass, а не массив)
        $json = json_decode(json_encode($json));

        if (!$json || !isset($json->mycryptocheckout)) {
            return;
        }

        try {
            // 4. Логируем входящее сообщение для отладки
            \Log::debug('MCC Webhook Received:', (array)$json);

            // 5. Вызываем обработку сообщений из родительского класса API
            $this->process_messages($json);

            // 6. Отправляем успешный ответ
            return response()->json(['result' => 'ok']);
        }
        catch (\Exception $e) {
            // 7. Логируем ошибку и отвечаем MCC
            \Log::error('MCC API failure: ' . $e->getMessage());

            return response()->json([
                'result' => 'fail',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function currencies()
    {
        if (isset($this->__currencies)) {
            return $this->__currencies;
        }

        $this->__currencies = new Currencies();
        $account_data = $this->account()->data;

        // Берем данные из вашего дампа [currency_data]
        $currency_data = $account_data->currency_data ?? [];

        foreach ($currency_data as $currency_id => $data) {
            // Создаем наш объект Currency, передавая ему данные и ссылку на API
            $currency = new Currency($currency_id, $data, $this);
            $this->__currencies->add($currency);
        }

        return $this->__currencies;
    }

    /**
     * Этот метод — "золотой ключ". Большинство компонентов SDK v2
     * ищут кошельки именно через этот вызов в API.
     */
    public function wallets()
    {
        $wallets = new Wallets();

        // Get wallets from database settings
        $setting = new \App\Models\Setting();
        $savedWallets = $setting->get_setting('mcc_wallets') ?: [];

        // Load usage history from DB
        $usage_history = $this->get_wallet_usage_history();

        foreach ((array)$savedWallets as $walletData) {
            $wallet = new \stdClass();
            $wallet->id = $walletData->id ?? null;
            $wallet->currency_id = $walletData->currency ?? '';
            $wallet->address = $walletData->address ?? '';
            $wallet->details = $walletData->details ?? null;
            $wallet->created_at = $walletData->created_at ?? null;

            // Add timestamp from history
            $wallet->last_used = $usage_history[$wallet->address] ?? 0;

            $wallets->push($wallet);
        }

        return $wallets;
    }

    /**
     * Вспомогательные методы для сохранения "пыльности"
     */
    protected function get_wallet_usage_history()
    {
        // Используем Laravel Cache или отдельную таблицу, чтобы хранить timestamp использования
        return \Cache::get('mcc_wallets_usage', []);
    }

    public function mark_wallet_as_used($address)
    {
        $history = $this->get_wallet_usage_history();
        $history[$address] = time();
        \Cache::forever('mcc_wallets_usage', $history);
    }

    public function createPayment($order, $currency_id)
    {
        // 1. Проверка доступности аккаунта
        if (!$this->account()->is_available_for_payment()) {
            throw new \Exception("MCC Account not available");
        }

        // 2. Получаем объект валюты для расчетов
        // (Используем метод currencies(), который мы ранее переписали для LaravelAPI)
        $currency = $this->currencies()->get($currency_id);
        if (!$currency) throw new \Exception("Currency $currency_id not supported.");

        // 3. Расчитываем сумму (из 100 USD в BTC)
        $fiat_amount = $order->full_price;
        $crypto_amount = $currency->convert('USD', $fiat_amount);

        // 4. Делаем сумму уникальной
        $unique_amount = $currency->find_next_available_amount($crypto_amount);

        // 5. Помечаем сумму как занятую в БД аккаунта
        $this->mark_amount_as_used($currency_id, $unique_amount);

        // 6. Выбор кошелька (логика ротации)
        // Выбираем "самый пыльный" кошелек
        $wallet = $this->wallets()->get_dustiest_wallet($currency_id);

        if (!$wallet) {
            throw new \Exception("No wallets configured for $currency_id");
        }

        // Помечаем в нашей истории, что мы его использовали
        $this->mark_wallet_as_used($wallet->address);

        // 7. Создание платежа
        $payment = $this->payments()->create_new();
        $payment->amount = $unique_amount;
        $payment->currency_id = $currency_id;
        $payment->to = $wallet->address; // Привязываем кошелек
        $payment->data()->set('order_id', $order->id);

        // Настройки таймаута (например, 48 часов)
        $payment->timeout_hours = 48;

        try {
            // 8. ГЛАВНЫЙ ШАГ: Отправка на сервер MCC
            // Этот метод отправит запрос к api.mycryptocheckout.com
            $payment = $this->send_payment($payment);

            // Если вызов успешен, сервер MCC вернет payment_id
            $mcc_payment_id = $payment->payment_id;

            // 9. Фиксируем успех локально
            $this->mark_amount_as_used($currency_id, $unique_amount);
            $this->mark_wallet_as_used($wallet->address);

            $order->update([
                'mcc_payment_id' => $mcc_payment_id,
                'mcc_payment_address'    => $payment->to,
                'mcc_crypto_amount'     => $payment->amount,
                'mcc_crypto_currency'   => $payment->currency_id,
                'mcc_status'     => 'waiting_payment',
                'status_id' => 1
            ]);

            return $payment;

        } catch (\Exception $e) {
            // Если, например, кончилась лицензия или неверный API ключ
            \Log::error("MCC Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Помечает сумму как занятую в локальных данных аккаунта
     */
    public function mark_amount_as_used($currency_id, $amount)
    {
        $account = $this->account();

        // 1. Проверяем, что data существует
        if (!isset($account->data->payment_amounts) || is_array($account->data->payment_amounts)) {
            $account->data->payment_amounts = new \stdClass();
        }

        // 2. Исправляем: если payment_amounts - массив, превращаем в объект
        if (!isset($account->data->payment_amounts) || is_array($account->data->payment_amounts)) {
            $account->data->payment_amounts = new \stdClass();
        }

        // 3. Аналогично для конкретной валюты
        if (!isset($account->data->payment_amounts->{$currency_id})) {
            $account->data->payment_amounts->{$currency_id} = new \stdClass();
        }

        $now = time();
        $timeout = 24 * 3600; // 24 часа

        // Необязательно, но полезно: чистим старье
        foreach ((array)$account->data->payment_amounts->{$currency_id} as $old_amount => $timestamp) {
            if ($now - $timestamp > $timeout) {
                unset($account->data->payment_amounts->{$currency_id}->$old_amount);
            }
        }

        $amount_str = (string)$amount;

        // Сохраняем: ключ - сумма, значение - время
        $account->data->payment_amounts->{$currency_id}->$amount_str = time();

        // 4. Сохраняем обновленный JSON в базу данных
        $this->save_data('mcc_account_data', $account->data);
    }

    /**
     * Отправляет созданный платеж на сервер MCC для отслеживания.
     */
    public function send_payment($payment, $order_id = null)
    {
        // 1. Подготавливаем вложенный объект данных (тот самый Payment_Data)
        $extra_data = [
            'microtime' => preg_replace( '/.*\./', '', microtime( true ) ),
        ];

        if ($order_id) {
            $extra_data['order_id'] = $order_id;
        }

        // 2. Собираем основной пакет данных платежа
        $payment_data = [
            'amount'      => (string)$payment->amount, // Приводим к строке для надежности
            'currency_id' => (string)$payment->currency_id,
            'to'          => (string)$payment->to,
            'created_at'  => $payment->created_at ?? time(),
            // ВАЖНО: поле data должно быть JSON-строкой, а не массивом!
            'data'        => json_encode($extra_data),
        ];

        $data = [
            'payment' => $payment_data,
        ];

        // 3. Отправляем на правильный эндпоинт
        $response = $this->send_post_with_account('payment/add', $data);

        $json = $this->parse_response($response);

        if (isset($json->payment_id)) {
            $payment->set_id($json->payment_id);
        }

        return $payment;
    }

    /**
     * Запрашивает актуальный статус платежа с сервера.
     */
    public function retrieve_payment($payment)
    {
        $data = [
            'payment_id' => $payment->get_id(),
        ];

        $response = $this->send_post_with_account('retrieve_payment', $data);
        $json = $this->parse_response($response);

        // Обновляем данные в объекте платежа
        if (isset($json->payment)) {
            // Здесь можно использовать метод из SDK для наполнения объекта из JSON
            // Например: $payment->apply_data($json->payment);
        }

        return $json;
    }

    /**
     * Переопределяем парсинг ответа, чтобы убрать зависимость от WordPress (is_wp_error)
     */
    public function parse_response($response)
    {
        // Если $response — это объект от Laravel Http Client
        if ($response instanceof \Illuminate\Http\Client\Response) {
            if ($response->failed()) {
                throw new \Exception('MCC API Error: ' . $response->body());
            }
            $json = json_decode($response->body());
        } else {
            // Если пришел уже готовый объект/строка
            $json = is_string($response) ? json_decode($response) : $response;
        }

        if (!is_object($json)) {
            throw new \Exception('MCC API Error: Invalid JSON response');
        }

        if (isset($json->result) && $json->result === 'fail') {
            throw new \Exception('MCC API Error: ' . ($json->message ?? 'Unknown error'));
        }

        return $json;
    }
}
