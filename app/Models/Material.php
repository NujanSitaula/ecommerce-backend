<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'unit',
        'current_stock',
        'low_stock_threshold',
        'cost_per_unit',
        'supplier',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'low_stock_threshold' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_materials')
            ->withPivot('quantity_required')
            ->withTimestamps();
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->low_stock_threshold;
    }

    public function decrementStock(float $quantity): void
    {
        $this->current_stock = max(0, $this->current_stock - $quantity);
        $this->save();
    }

    public function incrementStock(float $quantity): void
    {
        $this->current_stock += $quantity;
        $this->save();
    }
}
