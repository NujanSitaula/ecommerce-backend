<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount_amount',
        'max_uses',
        'max_uses_per_user',
        'min_cart_total',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_cart_total' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }
}


