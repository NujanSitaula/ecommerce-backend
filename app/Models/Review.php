<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'author_name',
        'author_email',
        'rating',
        'title',
        'description',
        'status',
        'is_verified_purchase',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'moderated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moderatedBy()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}

