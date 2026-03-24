<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModification extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'modification_type',
        'order_item_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isItemAdded(): bool
    {
        return $this->modification_type === 'item_added';
    }

    public function isItemRemoved(): bool
    {
        return $this->modification_type === 'item_removed';
    }
}
