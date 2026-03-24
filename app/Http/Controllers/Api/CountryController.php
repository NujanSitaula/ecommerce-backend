<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::with('states')->where('is_active', true)->get();
        return CountryResource::collection($countries);
    }
}


