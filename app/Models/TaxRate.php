<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'state_id',
        'name',
        'tax_type',
        'rate',
        'shipping_taxable',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'shipping_taxable' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
