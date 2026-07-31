<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsCart extends Model
{
    protected $table = 'products_cart';
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_quantity'
    ];

    public $timestamps = false;

    public function cart()
    {
        return $this->belongsTo('App\Models\Cart');
    }

    public function product()
    {
//        return $this->hasMany('App\Models\Product', 'id','product_id');
        return $this->belongsTo('App\Models\Product');
    }
}
