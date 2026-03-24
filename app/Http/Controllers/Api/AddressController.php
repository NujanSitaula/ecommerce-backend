<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = Address::with(['country', 'state'])
            ->where('user_id', Auth::id())
            ->get();

        return AddressResource::collection($addresses);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'phone_alt' => 'nullable|string|max:50',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'is_default' => 'sometimes|boolean',
        ]);

        if (!Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // If setting default, unset others
        if (!empty($data['is_default'])) {
            Address::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $address = Address::create(array_merge($data, ['user_id' => Auth::id()]));

        return new AddressResource($address->load(['country', 'state']));
    }

    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'phone_alt' => 'nullable|string|max:50',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'is_default' => 'sometimes|boolean',
        ]);

        if (!empty($data['is_default'])) {
            Address::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $address->update($data);

        return new AddressResource($address->load(['country', 'state']));
    }

    public function destroy(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $address->delete();

        return response()->json(['message' => 'Deleted']);
    }
}


