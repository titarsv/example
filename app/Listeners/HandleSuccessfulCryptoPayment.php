<?php

namespace App\Listeners;

use App\Events\CryptoPaymentCompleted;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPaidMail;

class HandleSuccessfulCryptoPayment
{
    public function handle(CryptoPaymentCompleted $event)
    {
        $payment = $event->payment;

        // Находим заказ по ID, который мы передавали в MCC
        $order = Order::find($payment->order_id);

        if ($order && $order->status !== 'completed') {
            // 1. Обновляем статус заказа
            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
                'tx_id' => $payment->transaction_id // Хэш транзакции из блокчейна
            ]);

            // 2. Отправляем уведомление клиенту
            Mail::to($order->user->email)->send(new OrderPaidMail($order));

            // 3. Логируем для истории
            \Log::info("Order #{$order->id} successfully paid via MCC.");
        }
    }
}
