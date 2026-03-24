<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StateResource;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;

class AdminStateController extends Controller
{
    /**
     * List states for a specific country.
     */
    public function index(Country $country)
    {
        $states = $country->states()
            ->orderBy('name')
            ->get();

        return StateResource::collection($states);
    }

    /**
     * Create a new state for a country.
     */
    public function store(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['country_id'] = $country->id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $state = State::create($validated);

        return new StateResource($state);
    }

    /**
     * Update an existing state for a country.
     */
    public function update(Request $request, Country $country, State $state)
    {
        if ($state->country_id !== $country->id) {
            return response()->json([
                'message' => 'State does not belong to the specified country.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $state->update($validated);

        return new StateResource($state->fresh());
    }

    /**
     * Delete a state for a country.
     */
    public function destroy(Country $country, State $state)
    {
        if ($state->country_id !== $country->id) {
            return response()->json([
                'message' => 'State does not belong to the specified country.',
            ], 400);
        }

        // If there are tax rates or other relations in the future, guard here.
        $state->delete();

        return response()->json(null, 204);
    }
}

