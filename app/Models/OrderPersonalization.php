<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPersonalization extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'personalization_option_id',
        'value',
        'file_url',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function personalizationOption()
    {
        return $this->belongsTo(ProductPersonalizationOption::class);
    }
}
