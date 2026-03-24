<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FlashSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the products associated with this flash sale.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')
            ->withPivot('sort_order')
            ->orderBy('flash_sale_products.sort_order')
            ->withTimestamps();
    }

    /**
     * Check if the flash sale is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now->gte($this->starts_at) && $now->lte($this->ends_at);
    }

    /**
     * Scope to get the currently active flash sale.
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }
}
