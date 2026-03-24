<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPersonalizationOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'type',
        'required',
        'options',
        'max_length',
        'price_adjustment',
        'order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
        'price_adjustment' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function validateValue($value): bool
    {
        if ($this->required && empty($value)) {
            return false;
        }

        switch ($this->type) {
            case 'text':
                if ($this->max_length && strlen($value) > $this->max_length) {
                    return false;
                }
                return true;

            case 'number':
                return is_numeric($value);

            case 'select':
            case 'color':
                if (!$this->options || !is_array($this->options)) {
                    return false;
                }
                return in_array($value, $this->options);

            case 'checkbox':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']);

            case 'file_upload':
                // File validation should be done at upload time
                return !empty($value);

            default:
                return false;
        }
    }
}
