<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNote;
use Illuminate\Http\Request;

class AdminNoteController extends Controller
{
    /**
     * Get the quick note for the authenticated admin
     */
    public function show()
    {
        $note = AdminNote::firstOrCreate(
            ['user_id' => auth()->id()],
            ['content' => '']
        );

        return response()->json(['content' => $note->content ?? '']);
    }

    /**
     * Update the quick note
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
        ]);

        $note = AdminNote::updateOrCreate(
            ['user_id' => auth()->id()],
            ['content' => $validated['content'] ?? '']
        );

        return response()->json(['content' => $note->content ?? '']);
    }
}
