<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_slug',
        'quantity',
        'price',
        'subtotal',
        'is_made_to_order',
        'production_status',
        'production_started_at',
        'production_completed_at',
        'estimated_completion_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_made_to_order' => 'boolean',
        'production_started_at' => 'datetime',
        'production_completed_at' => 'datetime',
        'estimated_completion_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function personalizations()
    {
        return $this->hasMany(OrderPersonalization::class);
    }
}
