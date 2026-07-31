<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Request extends Entity
{
    use SoftDeletes;

    protected $table = 'requests';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'form',
        'name',
        'email',
        'comments',
        'additional_fields'
    ];

    public function getCreatedAtAttribute($attr){
        return Carbon::parse($attr);
    }

    public function getUpdatedAtAttribute($attr){
        return Carbon::parse($attr);
    }
}
