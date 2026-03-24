<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso2',
        'iso3',
        'phone_code',
        'is_active',
    ];

    public function states()
    {
        return $this->hasMany(State::class);
    }
}


