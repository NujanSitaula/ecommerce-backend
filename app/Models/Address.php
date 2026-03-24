<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'name',
        'phone',
        'phone_alt',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country_id',
        'state_id',
        'is_default',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}


