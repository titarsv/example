<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class BTCPayService
{
    protected string $url;
    protected string $apiKey;
    protected string $storeId;

    public function __construct()
    {
        $this->url = config('services.btcpay.url');
        $this->apiKey = config('services.btcpay.api_key');
        $this->storeId = config('services.btcpay.store_id');
    }

    public function createInvoice($amount, $currency, $orderId)
    {
        $response = Http::withHeaders([
                'Authorization' => 'token ' . config('services.btcpay.api_key'),
                'Content-Type' => 'application/json'
            ])->post("{$this->url}/api/v1/stores/{$this->storeId}/invoices", [
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => [
                    'orderId' => $orderId
                ],
                'checkout' => [
                    'speedPolicy' => 'MediumSpeed', // Ожидание 1 подтверждения
                    'redirectURL' => base_url('/thanks?order_id=' . $orderId),
                ]
            ]);

        if ($response->failed()) {
            throw new \Exception("BTCPay Error: " . $response->body());
        }

        return $response->json();
    }
}
