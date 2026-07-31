<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    public $table = 'wish_list';

    public $fillable = [
        'user_id',
        'product_id'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function product()
    {
        return $this->hasOne('App\Models\Product', 'id','product_id');
    }
}
