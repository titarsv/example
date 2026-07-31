<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsInOrder extends Model
{
    public $table = 'products_in_order';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'product_quantity',
        'product_sum'
    ];

    public function product()
    {
        return $this->hasOne('App\Models\Product', 'id','product_id');
    }


}
