<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactNumberResource;
use App\Models\ContactNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactNumberController extends Controller
{
    public function index()
    {
        $contacts = ContactNumber::where('user_id', Auth::id())->get();
        return ContactNumberResource::collection($contacts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'is_default' => 'sometimes|boolean',
        ]);

        if (!Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!empty($data['is_default'])) {
            ContactNumber::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $contact = ContactNumber::create(array_merge($data, ['user_id' => Auth::id()]));

        return new ContactNumberResource($contact);
    }

    public function update(Request $request, ContactNumber $contactNumber)
    {
        if ($contactNumber->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'is_default' => 'sometimes|boolean',
        ]);

        if (!empty($data['is_default'])) {
            ContactNumber::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $contactNumber->update($data);

        return new ContactNumberResource($contactNumber);
    }

    public function destroy(ContactNumber $contactNumber)
    {
        if ($contactNumber->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $contactNumber->delete();

        return response()->json(['message' => 'Deleted']);
    }
}


