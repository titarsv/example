<?php

namespace Modules\Payments\Models;

use WayForPay\SDK\Collection\ProductCollection;
use WayForPay\SDK\Credential\AccountSecretTestCredential;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Exception\WayForPaySDKException;
use WayForPay\SDK\Handler\ServiceUrlHandler;
use WayForPay\SDK\Domain\Client;
use WayForPay\SDK\Domain\Product;
use WayForPay\SDK\Wizard\PurchaseWizard;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\Order;
use App;

class WayForPay
{
    protected $credential;

    public function __construct()
    {
        $settings = new Setting();
        if($settings->get_setting('wayforpay_sandbox')){
            $this->credential = new AccountSecretTestCredential();
        }else{
            $this->credential = new AccountSecretCredential($settings->get_setting('wayforpay_account'), $settings->get_setting('wayforpay_secret'));
        }
    }

    public function getPaymentForm($order){
        $lang = App::getLocale();
        $return_url = env('APP_URL').'/thanks?order_id='.$order->id;
        $user_info = json_decode($order->user_info, true);
        $name = explode(' ', $user_info['name']);
        $email = $user_info['email'];
        $phone = $user_info['phone'];

        $purchase = PurchaseWizard::get($this->credential)
            ->setOrderReference('wfpuid_'.$order->id)
            ->setAmount($order->total_price)
            ->setCurrency('UAH')
            ->setOrderDate(new \DateTime())
            ->setMerchantDomainName(env('APP_URL'))
            ->setClient(new Client(
                !empty($name[0]) ? $name[0] : '',
                !empty($name[1]) ? $name[1] : '',
                $email,
                $phone,
                'Ukraine'
            ))
            ->setReturnUrl($return_url)
            ->setServiceUrl(env('APP_URL').'/api/wayforpay')
            ->setLanguage($lang);

        foreach($order->getProducts() as $item){
            $purchase ->setProducts(new ProductCollection(array(
                new Product($item['product']->name, $item['price'], $item['quantity'])
            )));
        }

        return str_replace('<form', '<form id="wayforpay-form" class="hidden"', $purchase->getForm()
            ->getAsString());
    }

    public function checkResponse(){
        try {
            $handler = new ServiceUrlHandler($this->credential);
            $response = $handler->parseRequestFromPostRaw();

            $transaction = $response->getTransaction();

            $data = \json_decode(file_get_contents('php://input'), TRUE);
            $order_id = str_replace('wfpuid_', '', $data['orderReference']);

            Log::info('WayForPay response:', [$data]);

            if(!empty($order_id)){
                $order = Order::find($order_id);
                if(!empty($order) && $order->status_id == 1 && $data['transactionStatus'] == 'Approved'){
                    $order->status_id = 3;
                    $order->save();
                }
            }

            return json_decode($handler->getSuccessResponse($transaction), true);
        } catch (WayForPaySDKException $e) {
            Log::error('WayForPay SDK exception:', $e->getMessage());
        }
    }
}