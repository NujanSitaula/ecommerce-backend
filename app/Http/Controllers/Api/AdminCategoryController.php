<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    /**
     * List categories for admin management.
     */
    public function index(Request $request)
    {
        $query = Category::query()->withCount('products');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (!is_null($request->query('is_active'))) {
            $isActive = filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($isActive)) {
                $query->where('is_active', $isActive);
            }
        }

        $categories = $query
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Create a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $category = Category::create($validated);

        return new CategoryResource($category);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
        ]);

        if (array_key_exists('parent_id', $validated)) {
            if ($validated['parent_id'] === $category->id) {
                return response()->json([
                    'message' => 'A category cannot be its own parent.',
                ], 422);
            }
        }

        $category->update($validated);

        return new CategoryResource($category->fresh());
    }

    /**
     * Delete a category if allowed.
     */
    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that has child categories.',
            ], 409);
        }

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that has products.',
            ], 409);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}

