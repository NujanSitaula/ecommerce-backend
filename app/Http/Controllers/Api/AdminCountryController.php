<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;

class AdminCountryController extends Controller
{
    /**
     * List countries for admin management.
     */
    public function index(Request $request)
    {
        $query = Country::query()->withCount('states');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('iso2', 'like', "%{$search}%")
                    ->orWhere('iso3', 'like', "%{$search}%");
            });
        }

        if (!is_null($request->query('is_active'))) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($isActive)) {
                $query->where('is_active', $isActive);
            }
        }

        $countries = $query
            ->orderBy('name')
            ->get();

        return CountryResource::collection($countries);
    }

    /**
     * Create a new country.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'iso2' => ['required', 'string', 'size:2', 'unique:countries,iso2'],
            'iso3' => ['nullable', 'string', 'size:3', 'unique:countries,iso3'],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $country = Country::create($validated);

        return new CountryResource($country->loadCount('states'));
    }

    /**
     * Update an existing country.
     */
    public function update(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'iso2' => ['sometimes', 'string', 'size:2', 'unique:countries,iso2,' . $country->id],
            'iso3' => ['sometimes', 'nullable', 'string', 'size:3', 'unique:countries,iso3,' . $country->id],
            'phone_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $country->update($validated);

        return new CountryResource($country->fresh()->loadCount('states'));
    }

    /**
     * Delete a country if allowed.
     */
    public function destroy(Country $country)
    {
        if ($country->states()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a country that has states. Please delete or reassign its states first.',
            ], 409);
        }

        $country->delete();

        return response()->json(null, 204);
    }
}

