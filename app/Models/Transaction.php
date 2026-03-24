<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'status',
        'amount',
        'currency',
        'stripe_payment_intent_id',
        'stripe_refund_id',
        'description',
        'metadata',
        'approved_by',
        'approved_at',
        'processed_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orderModifications()
    {
        return $this->hasMany(OrderModification::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRefundRequest(): bool
    {
        return $this->type === 'refund_request';
    }
}
