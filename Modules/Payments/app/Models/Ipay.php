<?php

namespace Modules\Payments\Models;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\OneSController;
use League\Flysystem\Exception;
use App\Models\Order;
use App;

class Ipay
{
    private $api_url = 'https://api.ipay.ua';
    // 3333333333333331
    // 3333333333333349
    // 3333333333333356
    private $sandbox_url = 'https://sandbox-checkout.ipay.ua/api302';
    private $mch_id;
    private $sign_key;

    /**
     * Ipay constructor.
     * @param $mch_id
     * @param $sign_key
     * @param bool $sandbox
     * @throws Exception
     */
    public function __construct($mch_id, $sign_key, $sandbox = false)
    {
        if (empty($mch_id)) {
            throw new Exception('public_key is empty');
        }
        if (empty($sign_key)) {
            throw new Exception('private_key is empty');
        }
        $this->mch_id = $mch_id;
        $this->sign_key = $sign_key;

        if($sandbox){
            $this->api_url = $this->sandbox_url;
        }
    }

    private function getAuth(){
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8" standalone="yes"?><payment></payment>');
        $xml->auth = new \SimpleXMLElement('<auth></auth>');
        $salt = sha1(microtime(true));
        $sign = hash_hmac('sha512', $salt, $this->sign_key);

        $xml->auth->mch_id = $this->mch_id;
        $xml->auth->salt = $salt;
        $xml->auth->sign = $sign;

        return $xml;
    }

    public function getPaymentUrl($order){
        $delivery = $order->getDeliveryInfo();
        $xml = $this->getAuth();

        $xml->urls = new \SimpleXMLElement('<urls></urls>');
        $xml->urls->good = env('APP_URL').'/thanks?order_id='.$order->id;
        $xml->urls->bad = env('APP_URL').'/thanks?order_id='.$order->id;

        $xml->transactions = new \SimpleXMLElement('<transactions></transactions>');
        $xml->transactions->transaction = new \SimpleXMLElement('<transaction></transaction>');
        $xml->transactions->transaction->amount = ($order->total_price - $order->total_sale + (isset($delivery['delivery_cost']) ? $delivery['delivery_cost'] : 0)) * 100;
        $xml->transactions->transaction->currency = 'UAH';
        $xml->transactions->transaction->desc = 'Оплата заказа №'.(!empty($order->external_id) ? $order->external_id : $order->id).' на сайте Exvelo';
        $xml->transactions->transaction->info = !empty($order->external_id) ? $order->external_id : $order->id;

        $xml->trademark = '{"ru":"Exvelo","ua":"Exvelo","en":"Exvelo"}';
        $xml->lifetime = 24;
        $xml->lang = App::getLocale();

        return $this->request($xml->asXML());
    }

    protected function request($xml){
        Log::info('Payment xml:', ['data' => $xml]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['data' => $xml]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $server_output = curl_exec($ch);
        curl_close($ch);
        return $this->xmlstring2array($server_output);
    }

    protected function xmlstring2array($string){
        $xml = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);
        $array = json_decode(json_encode($xml), TRUE);
        return $array;
    }

    public function updatePayment($xml){
        $data = $this->xmlstring2array($xml);
        if($data['sign'] === hash_hmac('sha512', $data['salt'], $this->sign_key)){
            $status = $data['status'];
            $order_id = $data['transactions']['transaction'][0]['info'];
            $order = Order::find($order_id);
            if(!empty($order)){
                if($status == 5 && $order->status_id == 1){
                    $order->status_id = 2;
                    $order->save();
                    $one_c = new OneSController();
                    $one_c->updateOrderStatus($order->id);
                }
                return true;
            }
        }

        return true;
    }
}