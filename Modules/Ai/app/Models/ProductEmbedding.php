<?php

namespace Modules\Ai\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductEmbedding extends Model
{
    protected $table = 'product_embeddings';

    protected $fillable = [
        'product_id',
        'locale',
        'model',
        'dimensions',
        'vector',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getVectorArrayAttribute(): array
    {
        return json_decode($this->vector, true) ?: [];
    }
}