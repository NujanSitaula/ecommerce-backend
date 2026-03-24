<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchHistoryController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $items = SearchHistory::where('user_id', $userId)
            ->orderByDesc('searched_at')
            ->limit(20)
            ->get(['id', 'query', 'searched_at']);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'query' => 'required|string|max:255',
        ]);

        $query = trim(mb_strtolower($data['query']));
        if ($query === '') {
            return response()->json(['message' => 'Query is required'], 422);
        }

        $item = SearchHistory::updateOrCreate(
            ['user_id' => $userId, 'query' => $query],
            ['searched_at' => now()]
        );

        // Keep only last 20 items
        $idsToKeep = SearchHistory::where('user_id', $userId)
            ->orderByDesc('searched_at')
            ->limit(20)
            ->pluck('id');

        SearchHistory::where('user_id', $userId)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        return response()->json([
            'id' => $item->id,
            'query' => $item->query,
            'searched_at' => $item->searched_at,
        ]);
    }

    public function destroy($id)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $item = SearchHistory::where('user_id', $userId)->where('id', $id)->first();
        if (! $item) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $item->delete();
        return response()->json(['ok' => true]);
    }

    public function clear()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        SearchHistory::where('user_id', $userId)->delete();
        return response()->json(['ok' => true]);
    }
}

