<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'order_id',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeSales($query)
    {
        return $query->where('type', 'sale');
    }

    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('type', 'adjustment');
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
