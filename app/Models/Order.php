<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_email',
        'guest_name',
        'guest_address',
        'address_id',
        'contact_number_id',
        'payment_method_id',
        'coupon_id',
        'stripe_payment_intent_id',
        'delivery_date',
        'gift_wrapped',
        'delivery_instructions',
        'leave_at_door',
        'subtotal',
        'shipping_fee',
        'discount_amount',
        'shipping_discount',
        'tax_amount',
        'tax_rate',
        'tax_type',
        'total',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'guest_address' => 'array',
        'delivery_date' => 'date',
        'gift_wrapped' => 'boolean',
        'leave_at_door' => 'boolean',
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function contactNumber()
    {
        return $this->belongsTo(ContactNumber::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function modifications()
    {
        return $this->hasMany(OrderModification::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }
}
