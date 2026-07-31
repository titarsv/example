<?php

namespace App\Models;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App;

class Coupon extends Entity
{
    protected $fillable = [
    	'name',
    	'code',
    	'user_id',
        'price',
        'percent',
        'from',
        'to',
        'disposable',
        'used',
        'scope',
        'statistic',
        'min_total',
        'without_sale',
        'status'
    ];

    public $entity_type = 'coupon';
    protected $table = 'coupons';

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function product(){
        return $this->hasOne('App\Models\Product', 'id', 'product_id');
    }

    public function getScopeAttribute(){
        return json_decode($this->attributes['scope']);
    }

    public function getScopeTypeAttribute(){
        $scope = $this->scope;

        if(!empty($scope) && !empty($scope->type)){
            return $scope->type;
        }

        return 'all';
    }

    public function getScopeItemsAttribute(){
        $scope = $this->scope;

        if(!empty($scope) && !empty($scope->ids)){
            return (array)$scope->ids;
        }

        return [];
    }

    public function generateCoupon($data){
        $code = Str::random();
        $i = 0;
        while(!empty($this->where('code', $code)->first())){
            $code = Str::random();
            $i++;
            if($i == 100){
                break;
            }
        }

        $coupon_id = $this->insertGetId([
            'code' => $code,
            'user_id' => isset($data['user_id']) ? $data['user_id'] : null,
            'product_id' => isset($data['product_id']) ? $data['product_id'] : null,
            'price' => isset($data['price']) ? $data['price'] : null,
            'percent' => isset($data['percent']) ? $data['percent'] : null,
            'shelf_life' => isset($data['shelf_life']) ? $data['shelf_life'] : null,
            'disposable' => isset($data['disposable']) ? $data['disposable'] : 1
        ]);

        $coupon = $this->find($coupon_id);

        if(!empty($data['send_to'])){
            $this->sendCoupon($coupon,$data['send_to']);
        }

        return $coupon;
    }

    private function sendCoupon($coupon, $email){
//        Mail::send('emails.certificate', ['coupon' => $coupon], function($msg) use ($email, $coupon){
//            $msg->from('admin@'.str_replace(['http://', 'https://'], '', env('APP_URL')), 'Интернет-магазин Exvelo');
//            $msg->to($email);
//            $msg->subject($coupon->name);
//        });
    }

    protected function dataMap(){
        return [
            'attributes' => []
        ];
    }

    public function fieldsNames(){
        return [
            'attributes.code' => [
                'name' => 'Код купона'
            ],
            'attributes.user_id' => [
                'name' => 'ID владельца'
            ],
            'attributes.product_id' => [
                'name' => 'ID товара'
            ],
            'attributes.price' => [
                'name' => 'Сумма скидки'
            ],
            'attributes.percent' => [
                'name' => 'Процент скидки'
            ],
            'attributes.disposable' => [
                'name' => 'Разовый'
            ]
        ];
    }
}
