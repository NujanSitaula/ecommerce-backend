<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'attributes',
        'price',
        'sale_price',
        'quantity',
        'image_url',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
