<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'brand',
        'last4',
        'cardholder_name',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


