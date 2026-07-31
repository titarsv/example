<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Coupons\Models\Coupon;

class Order extends Entity
{
    public $table = 'orders';
    protected $fillable = [
        'external_id',
        'user_id',
        'total_price',
        'total_quantity',
        'coupon_sale',
        'crypto_sale',
        'amount_sale',
        'total_sale',
        'status_id',
        'products',
        'user_info',
        'comment',
        'delivery',
        'payment',
        'payment_status',
        'mcc_payment_address',
        'mсc_payment_id',
        'mсc_crypto_amount',
        'mcc_crypto_currency',
        'mcc_status',
        'mcc_attempts',
        'notes',
        'history',
        'viewed'
    ];

    public static function boot(){
        parent::boot();

        self::updating(function($model){
            if($model->original['status_id'] != $model->attributes['status_id'] && $model->original['status_id'] == 1 && in_array($model->attributes['status_id'], [2,3,4])){
                $saved_products = json_decode($model->products, true);
                $products = $model->getProducts();
                $coupons = new Coupon();

                foreach($products as $key => $product){
                    if($product['product']->certificate && empty($product['generated']) && module_active('coupons')){
                        $price = $product['product']->original_price;

                        $coupons->generateCoupon([
                            'price' => $price,
                            'send_to' => $model->getEmailAttribute()
                        ]);

                        $saved_products[$product['product_code']]['generated'] = true;
                    }
                }

                $model->products = json_encode($saved_products, JSON_UNESCAPED_UNICODE);

                if (module_active('ai')) {
                    $product_ids = array_map(function($code){
                        return (int) explode('_', (string) $code)[0];
                    }, array_keys($saved_products));

                    \Modules\Ai\Jobs\UpdateBoughtTogetherJob::dispatch($product_ids);
                }
            }

            // Send paid order email when status changes to 3
            if($model->original['status_id'] != $model->attributes['status_id'] && $model->attributes['status_id'] == 3){
                $order_user = json_decode($model->user_info, true);
                \App\Jobs\SendPaidOrderEmailJob::dispatch($model->id, $order_user, $model)
                    ->delay(now()->addMinutes(1));
            }
        });

        // Синхронизация order_products с JSON-колонкой products — таблица используется как
        // структурированный источник для агрегаций (см. recommendations:bought-together)
        self::created(function($model){
            $model->syncOrderProducts();
        });

        self::updated(function($model){
            if($model->wasChanged('products')){
                $model->syncOrderProducts();
            }
        });
    }

    /**
     * Синхронизирует строки order_products с содержимым JSON-колонки products.
     */
    public function syncOrderProducts(){
        DB::table('order_products')->where('order_id', $this->id)->delete();

        if(empty($this->products)){
            return;
        }

        $products = json_decode($this->products, true);
        if(!is_array($products)){
            return;
        }

        $rows = [];
        foreach($products as $product_code => $data){
            $product_id = (int) explode('_', (string) $product_code)[0];
            if($product_id <= 0 || !is_array($data)){
                continue;
            }

            $extra = $data;
            unset($extra['quantity'], $extra['price'], $extra['sale']);

            $rows[] = [
                'order_id' => $this->id,
                'product_id' => $product_id,
                'qty' => (int) ($data['quantity'] ?? 1),
                'price' => (float) ($data['price'] ?? 0),
                'sale' => (float) ($data['sale'] ?? 0),
                'data' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ];
        }

        if(!empty($rows)){
            DB::table('order_products')->insert($rows);
        }
    }

    public function products(){
        return $this->belongsToMany('App\Models\Product', 'order_products', 'order_id', 'product_id')
            ->withPivot('qty', 'price', 'sale', 'data');
    }

    public function coupon(){
        return $this->hasOne(Coupon::class, 'id', 'coupon_id');
    }

    public function getFirstNameAttribute(){
        $userInfo = $this->getUserInfo();
        if(isset($userInfo->first_name) && !empty(trim($userInfo->first_name)))
            return $userInfo->first_name;
        else{
            $user = $this->user;
            return !empty($user->first_name) ? $user->first_name : '';
        }
    }

    public function getLastNameAttribute(){
        $userInfo = $this->getUserInfo();
        if(isset($userInfo->last_name) && !empty(trim($userInfo->last_name)))
            return $userInfo->last_name;
        else{
            $user = $this->user;
            return !empty($user->last_name) ? $user->last_name : '';
        }
    }

    public function getEmailAttribute(){
        $userInfo = $this->getUserInfo();
        if(!empty($userInfo->email))
            return $userInfo->email;
        else{
            $user = $this->user;
            return !empty($user->email) ? $user->email : '';
        }
    }

    public function getPhoneAttribute(){
        $userInfo = $this->getUserInfo();
        if(!empty($userInfo->phone))
            return $userInfo->phone;
        else{
            $user = $this->user;
            return !empty($user->phone) ? $user->phone : '';
        }
    }

    public function getCityAttribute(){
        $deliveryInfo = $this->getDeliveryInfo();
        if(!empty($deliveryInfo['city']))
            return $deliveryInfo['city'];
        else{
//            $user = $this->user;
//            return !empty($user->getAddress()->city) ? $user->getAddress()->city : '';
            return '';
        }
    }

    public function getDeliveryMethodAttribute(){
        $deliveryInfo = $this->getDeliveryInfo();
        if(!empty($deliveryInfo['method']))
            return $deliveryInfo['method'];
        else{
            return '';
        }
    }

    public function getAddressAttribute(){
        $deliveryInfo = $this->getDeliveryInfo();
        if($deliveryInfo['method'] == trans('app.courier_to_your_address'))
            return (isset($deliveryInfo['street']) ? $deliveryInfo['street'] : '').
                (isset($deliveryInfo['house']) ? ' '.$deliveryInfo['house'] : '').
                (isset($deliveryInfo['apartment']) ? ', кв.'.$deliveryInfo['apartment'] : '');
        elseif($deliveryInfo['method'] == trans('app.new_mail'))
            return isset($deliveryInfo['warehouse']) ? $deliveryInfo['warehouse'] : '';
        elseif($deliveryInfo['method'] == trans('app.pickup_from_justin'))
            return $deliveryInfo['warehouse'];
        elseif($deliveryInfo['method'] == trans('app.pickup_from_ukrposhta'))
            return $deliveryInfo['street'].
                ' '.$deliveryInfo['house'].
                ', кв.'.$deliveryInfo['apart'].
                ', '.$deliveryInfo['index'];
        else{
            return '';
        }
    }

    public function getStreetAttribute(){
        $deliveryInfo = $this->getDeliveryInfo();
        if(!empty($deliveryInfo['street']))
            return $deliveryInfo['street'];
        else{
            return '';
        }
    }

    public function getPaymentMethodAttribute(){
        $methods = [
            'cash' => trans('app.cash'),
            'card' => trans('app.bank_card'),
            'online' => trans('app.online'),
            'prepayment' => trans('app.cashless')
        ];
        if(isset($methods[$this->payment]))
            return $methods[$this->payment];
        else{
            return '';
        }
    }

    public function getUserInfo(){
        return json_decode($this->user_info);
    }

    /**
     * Связь с моделью статусов заказа
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status(){
        return $this->belongsTo('App\Models\OrderStatus', 'status_id');
    }

    /**
     * Получение незавершенного заказа по id пользователя
     *
     * @param $user
     * @return int
     */
    public function getCurrentIncompleteOrder($user){
        if($user){
            $order = $this->where('user_id', $user->id)->where('status_id', 0)->first();

            if (!is_null($order)) {
                return $order->id;
            }
        }

        return 0;
    }

    /**
     * Товары в заказе
     *
     * @return array
     */
    public function getProducts(){
        $products = json_decode($this->products, true);
        $result = [];

        foreach ($products as $product_code => $data) {
            $variation_attrs = [];

            if(!empty($data['variation'])){
                $v = new Variation();
                $variation = $v->find($data['variation']);
                if(!empty($variation)){
                    $values = $variation->attribute_values;
                    foreach($values as $value){
                        $attr = $value->attribute;
                        if(!isset($variation_attrs[$attr->name])){
                            $variation_attrs[$attr->name] = $value->name;
                        }
                    }
                }
            }

            $product_vars = explode('_', $product_code);
            $result[] = [
                'product'   => Product::where('id', $product_vars[0])->with('attributes.value', 'image')->first(),
                'quantity'  => $data['quantity'],
                'variations'  => $variation_attrs,
                'price'  => $data['price'],
	            'product_code' => $product_code,
	            'product_sum' => $data['price']*$data['quantity'] * (100 - $data['sale_percent']) / 100 - $data['sale']
            ];
        }

        return $result;
    }

    /**
     * Информация о доставке в удобночитаемом формате
     *
     * @return array
     */
    public function getDeliveryInfo(){
        $delivery_info = json_decode($this->delivery, true);
        $locale = App::getLocale();
        if(empty($delivery_info)){
        	return null;
        }

        return $delivery_info;
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function getDeliveryCostAttribute(){
        $delivery = json_decode($this->delivery);

        return isset($delivery->delivery_cost) ? $delivery->delivery_cost : 0;
    }

    public function getFullPriceAttribute(){
        $delivery = json_decode($this->delivery);

        return $this->total_price + (isset($delivery->delivery_cost) ? $delivery->delivery_cost : 0) - $this->total_sale;
    }

    public function getHistory(){
        return json_decode($this->history);
    }

    public function getPaymentNameAttribute(){
        $settings = new Setting();

        return $settings->get_setting('payment_'.$this->payment.'_name_'.app()->getLocale());
    }
}
