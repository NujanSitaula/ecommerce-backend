<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class AdminTagController extends Controller
{
    /**
     * List tags for admin (used for autocomplete).
     */
    public function index(Request $request)
    {
        $query = Tag::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $tags = $query->orderBy('name')->limit(100)->get();

        return response()->json($tags);
    }
}

