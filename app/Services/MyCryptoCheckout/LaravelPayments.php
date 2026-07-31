<?php

namespace App\Services\MyCryptoCheckout;

use mycryptocheckout\api\v2\Payments as BasePayments;
use mycryptocheckout\api\v2\Payment;
use mycryptocheckout\api\v2\Exception;
use Illuminate\Support\Facades\Event;
use App\Models\Order;

class LaravelPayments extends BasePayments
{
    /**
     * Вызывается, когда сервер MCC говорит, что платеж отменен (таймаут).
     */
    public function cancel_local($payment)
    {
        $order = Order::where('mcc_payment_id', $payment->get_id())->first();

        if ($order) {
            $order->update(['mcc_status' => 'canceled']);

            \Log::info("MCC: Payment canceled for Order #{$order->id}");

            // Очищаем занятую сумму
            $this->api()->release_used_amount($order->mcc_currency, $order->mcc_amount);
        }
    }

    /**
     * Вызывается, когда сервер MCC говорит, что оплата получена.
     */
    public function complete_local($payment)
    {
        // Ищем заказ по mcc_payment_id, который пришел в сообщении
        $order = Order::where('mcc_payment_id', $payment->get_id())->first();

        if ($order) {
            // Получаем сумму, которую фактически увидел сервер MCC в блокчейне
            $actual_amount = $payment->get_amount();

            // Получаем ожидаемую сумму из вашего заказа
            $expected_amount = $order->mсc_crypto_amount;

            // Если разница критическая (например, больше 1% - на ваше усмотрение)
            if (abs($actual_amount - $expected_amount) > ($expected_amount * 0.01)) {
                $order->update([
                    'status' => 'manual_check', // Отправляем на ручную проверку
                    'mcc_actual_amount' => $actual_amount
                ]);
                \Log::warning("Order #{$order->id} paid with wrong amount: Expected $expected_amount, got $actual_amount");
            } else {
                $order->update([
                    'payment_status ' => 1,
                    'mcc_status' => 'paid',
                    'status' => $order->status == 'waiting_payment' ? 'paid' : $order->status
                ]);

                \Log::info("MCC: Payment completed for Order #{$order->id}");

                // Очищаем занятую сумму
                $this->api()->release_used_amount($order->mcc_currency, $order->mcc_amount);
            }
        }
    }

    /**
     * Создание объекта платежа.
     * Убираем логику MULTISITE (WordPress Network).
     */
    public static function create_new($data = null)
    {
        // Вызываем базовый метод из ядра SDK (BasePayments)
        $payment = parent::create_new($data);

        // Вместо логики WP Multisite, мы можем добавить ID нашего приложения
        // или просто оставить метод пустым, так как Laravel обычно работает в один инстанс.
        $payment->data()->set('site_url', config('app.url') . '/api/mcc_callback');

        return $payment;
    }

    public function do_local($message_type, \mycryptocheckout\api\v2\Payment $payment)
    {
        // Вместо создания "Action" через WP-хелпер, просто используем Laravel Events
        if ($message_type === 'complete_payment') {
            event(new \App\Events\CryptoPaymentCompleted($payment));
        }

        if ($message_type === 'cancel_payment') {
            event(new \App\Events\CryptoPaymentCancelled($payment));
        }

        $this->api()->debug($message_type . ' event dispatched for payment ' . $payment->order_id);
    }

    public function send($order_id)
    {
        // 1. Получаем заказ
        $order = \App\Models\Order::find($order_id);
        if(!$order){
            $this->api()->debug('Order %d not found.', $order_id);
            return;
        }

        $attempts = (int) $order->mcc_attempts;

        $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();

        // Блокируем аккаунт для синхронизации (стандартная логика SDK)
        $this->api()->account()->lock()->save();

        try {
            // 2. Генерируем объект платежа
            $payment = static::generate_payment_from_order($order);

            // 3. Отправляем платеж в API (метод add вызывает API MCC)
            $payment_id = $this->add($payment);

            // 4. Сохраняем результат
            $order->mcc_payment_id = $payment_id;
            $order->save();

            $this->api()->debug('Payment for order %d has been added as payment #%d.', $order_id, $payment_id);
        }
        catch (\Exception $e) {
            // 5. Обработка ошибок
            $attempts++;
            $order->mcc_attempts = $attempts;

            $this->api()->debug('Failure #%d trying to send payment for order %d. %s', $attempts, $order_id, $e->getMessage());

            if ($attempts > 1440) { // 24 часа
                $this->api()->debug('Giving up on order %d.', $order_id);
                $order->mcc_payment_id = -1; // Ставим -1, чтобы больше не пытаться
            } else {
                // В Laravel вместо wp_schedule_single_event используем отложенные задачи (Queues)
                // Или полагаемся на то, что send_unsent_payments() запустится планировщиком позже
                $this->api()->debug('Will try again later for order %d.', $order_id);
            }

            $order->save();

            // Разблокируем аккаунт в случае неудачи
            $this->api()->account()->unlock()->save();
        }
    }

    /**
     * Генерирует объект платежа из модели заказа Laravel.
     *
     * @param  mixed  $order  Модель заказа или ID заказа
     * @return \mycryptocheckout\api\v2\Payment
     */
    public static function generate_payment_from_order( $order )
    {
        // Если передали ID вместо модели, находим модель
        if ( ! is_object( $order ) )
            $order = \App\Models\Order::findOrFail( $order );

        // Создаем пустой объект платежа через SDK
        $payment = static::create_new();

        // Заполняем данными из модели заказа
        $payment->amount = $order->mcc_crypto_amount;
        $payment->currency_id = $order->mcc_crypto_currency;
        $payment->to = $order->mcc_payment_address; // Привязываем кошелек
        $payment->data()->set('order_id', $order->id);

        // Настройки таймаута (например, 2 часа)
        $payment->timeout_hours = 2;

        return $payment;
    }

    /**
     * Переопределяем отправку неотправленных платежей для Laravel.
     */
    public function send_unsent_payments()
    {
        // 1. Находим все заказы, у которых в базе payment_id равен '0' или null
        $orders = \App\Models\Order::where('payment', 'mycryptocheckout')
            ->where(function($query){
                $query->where('mcc_payment_id', '0')
                    ->orWhereNull('mcc_payment_id');
            })
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $this->api()->debug('Unsent payments for Order IDs: %s', $orders->pluck('id')->implode(', '));

        // 2. Проходим циклом по заказам и вызываем метод send()
        foreach ($orders as $order) {
            // В оригинальном SDK send() принимает ID поста.
            // В вашей реализации send() должен уметь работать с ID вашего заказа.
            $this->send($order->id);
        }
    }
}
