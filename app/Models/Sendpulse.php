<?php

namespace App\Models;

use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;

class Sendpulse
{
    private $api_user_id = null;
    private $api_secret = null;
    private $SPApiClient = null;

    public function __construct()
    {
        $this->api_user_id = env('SENDPULSE_USER_ID', null);
        $this->api_secret = env('SENDPULSE_SECRET', null);

        if(!empty($this->api_user_id) && !empty($this->api_secret))
            $this->SPApiClient = new ApiClient($this->api_user_id, $this->api_secret, new FileStorage());
    }

    public function order($user, $order){
        $delivery = $order->getDeliveryInfo();
        $data = [];

        $data['email'] = $user['email'];
        $data['phone'] = str_replace(['(', ')', ' ', '-'], '', $user['phone']);
        $data['order_number'] = !empty($order->external_id) ? $order->external_id : $order->id;
        $data['product'] = [];
        $data['name'] = $user['first_name'];
        $data['surname'] = $user['last_name'];
        $data['address'] = '';
        if(isset($delivery['region'])){
            $data['address'] .= $delivery['region'].' обл., ';
        }
        if(isset($delivery['city'])){
            $data['address'] .= $delivery['city'].', ';
        }
        if(isset($delivery['warehouse'])){
            $data['address'] .= $delivery['warehouse'];
        }else{
            if(isset($delivery['street'])){
                $data['address'] .= $delivery['street'].' ';
                if(isset($delivery['house'])){
                    $data['address'] .= $delivery['house'];
                    if(isset($delivery['apart'])){
                        $data['address'] .= ', кв.'.$delivery['apart'];
                    }elseif(isset($delivery['apartment'])){
                        $data['address'] .= ', кв.'.$delivery['apartment'];
                    }
                }
            }

            if(isset($delivery['index'])){
                $data['address'] .= ', '.$delivery['index'];
            }
        }
        $data['delivery_method'] = isset($delivery['method']) ? $delivery['method'] : '';
        if ($order->payment == 'cash'){
            $data['payment_method'] = "Оплата наличными при получении";
        }elseif($order->payment == 'prepayment'){
            $data['payment_method'] = "Предоплата";
        }elseif($order->payment == 'privat'){
            $data['payment_method'] = "На расчетный счет Приват Банка";
        }elseif($order->payment == 'card'){
            $data['payment_method'] = "Кредитной картой";
        }elseif($order->payment == 'nal_delivery'){
            $data['payment_method'] = "Наличными курьеру";
        }elseif($order->payment == 'nal_samovivoz'){
            $data['payment_method'] = "Оплата при самовывозе";
        }elseif($order->payment == 'nalogenniy'){
            $data['payment_method'] = "Оплата наложенным платежом";
        }
        $data['total'] = $order->total_price - $order->total_sale + (isset($delivery['delivery_cost']) ? $delivery['delivery_cost'] : 0);
        $data['sum'] = $order->total_price;
        $data['discount'] = $order->total_sale;

        foreach($order->getProducts() as $item) {
            $name = $item['product']->name;
            if (!empty($item['variations'])){
                $name .= ' (';
                foreach ($item['variations'] as $n => $val) {
                    $name .= $n.': '.$val.';';
                }
                $name .= ')';
            }

            $data['product'][] = [
                'product_name' => $name,
                'product_link' => $item['product']->link(),
                'product_price' => $item['price'],
                'product_img_url' => !empty($item['product']->image) ? url($item['product']->image->url([100, 100])) : url('/uploads/no_image.jpg'),
                'product_volume' => $item['quantity']
            ];
        }

        return $this->request('7d04849147ae4e448e91932cc4bf9483/7487931', 'POST', $data);
    }

    public function quiz($email, $phone, $name, $answers, $promocode = ''){
        $data = [
            'email' => $email,
            'phone' => $phone,
            'name' => $name,
            'hair' => '',
            'promocode' => $promocode
        ];

        $fields = ['age', 'sex', 'water', 'sleep', 'pregnancy', 'sun', 'skin', 'problem', 'long', 'painted', 'effect', 'beauty'];

        foreach($fields as $i => $field){
            $data[$field] = isset($answers[$i]) ? mb_substr(trim($answers[$i]), 0, 255) : '';
        }

        return $this->request('74f6e2039eb24051beefbbd65f2dfc78/7487931', 'POST', $data);
    }

    public function resetPassword($email, $phone, $login, $password){
        $data = [
            "email" => $email,
            "phone" => $phone,
            "login" => $login,
            "password" => $password
        ];

        return $this->request('cc5fc99c36e544b88fafe483bf71d66e/7487931', 'POST', $data);
    }

    public function subscribe($email){
        $data = [
            'emails' => [['email' => $email]],
            'confirmation' => 'force',
            'sender_email' => 'hello@shop.gbar.ua'
        ];
        $url = 'https://api.sendpulse.com/addressbooks/1059658/emails';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json', $this->getToken()]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSLVERSION, 6);
        $output = curl_exec($ch);
        curl_close($ch);

        if(substr($output, 0, 1) !== '{' && substr($output, 0, 1) !== '['){
            return $output;
        }

        return json_decode($output);
    }

    public function getToken(){
        $storage = storage_path('app'.DIRECTORY_SEPARATOR.'sendpulse.json');
        if(is_file($storage)){
            $result = json_decode(file_get_contents($storage));
            if(!empty($result) && isset($result->token_type) && isset($result->access_token) && isset($result->expires_in) && $result->expires_in > time()){
                return 'Authorization: ' . $result->token_type . ' ' . $result->access_token;
            }
        }

        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => '056ae427ae847987c42d0d641cdc9d78',
            'client_secret' => '12f1a293d2e3d428beac67e9ed7f3b6f'
        ];
        $url = 'https://api.sendpulse.com/oauth/access_token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSLVERSION, 6);
        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($output);

        $result->expires_in += time();
        file_put_contents($storage, json_encode($result));

        return 'Authorization: ' . $result->token_type . ' ' . $result->access_token;
    }

    public function request($id, $method = 'GET', $data = []){
        $url = 'https://events.sendpulse.com/events/id/'.$id;
        $ch = curl_init();
        switch ($method){
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                if($data)
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                if($data)
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
                break;
            default:
                if($data){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                }
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSLVERSION, 6);
        $output = curl_exec($ch);
        curl_close($ch);
        if(substr($output, 0, 1) !== '{' && substr($output, 0, 1) !== '['){
            return $output;
        }
        return json_decode($output);
    }
}