<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    protected $table = 'users_data';

    protected $fillable = [
        'user_id',
        'image_id',
        'phone',
        'address',
        'company',
        'other_data',
        'subscribe'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    public function image()
    {
        return $this->belongsTo('App\Models\Image')->withDefault(['id' => 1, 'href' => 'no_image.jpg', 'title' => 'Изображение не выбрано', 'type' => 'default', 'sizes' => '{"100_100":{"href":"no_image_100x100.jpg","w":100,"h":100}}']);
    }

    public function address()
    {
        return json_decode($this->address);
    }
}
