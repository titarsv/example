<?php

namespace App\Models;

use Illuminate\Support\Facades\Redis;

class ProductAttributes extends Entity
{
    protected $table = 'product_attributes';

    protected $fillable = [
        'product_id',
        'attribute_id',
        'attribute_value_id'
    ];

    public $timestamps = false;

    // Автоматизация сохранения в Redis
    public static function boot(){
        parent::boot();

        self::creating(function($model){

        });

        self::created(function($model){
            if(env('REDIS_CACHE')) {
                Redis::command('setbit', ["attribute_$model->attribute_value_id", $model->product_id, 1]);
            }
        });

        self::updating(function($model){

        });

        self::updated(function($model){
            if(env('REDIS_CACHE')) {
                Redis::command('setbit', ["attribute_$model->attribute_value_id", $model->product_id, 1]);
            }
        });

        self::deleting(function($model){

        });

        self::deleted(function($model){
            if(env('REDIS_CACHE')) {
                Redis::command('setbit', ["attribute_$model->attribute_value_id", $model->product_id, 0]);
            }
        });
    }

    public function product(){
        return $this->belongsTo('App\Models\Product');
    }

    public function info(){
        return $this->hasOne('App\Models\Attribute', 'id', 'attribute_id');
    }

    public function value(){
        return $this->hasOne('App\Models\AttributeValue', 'id', 'attribute_value_id');
    }


}
