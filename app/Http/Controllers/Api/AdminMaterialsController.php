<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class AdminMaterialsController extends Controller
{
    /**
     * List all materials
     */
    public function index(Request $request)
    {
        $query = Material::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'low_stock_threshold');
        }

        $materials = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $materials->items(),
            'current_page' => $materials->currentPage(),
            'last_page' => $materials->lastPage(),
            'per_page' => $materials->perPage(),
            'total' => $materials->total(),
        ]);
    }

    /**
     * Create a material
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'current_stock' => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
        ]);

        $material = Material::create($request->all());

        return response()->json([
            'message' => 'Material created successfully',
            'data' => $material,
        ], 201);
    }

    /**
     * Update a material
     */
    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'sometimes|string|max:50',
            'current_stock' => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
        ]);

        $material->update($request->all());

        return response()->json([
            'message' => 'Material updated successfully',
            'data' => $material->fresh(),
        ]);
    }

    /**
     * Delete a material
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();

        return response()->json([
            'message' => 'Material deleted successfully',
        ]);
    }

    /**
     * Adjust material stock
     */
    public function adjustStock(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'quantity' => 'required|numeric',
            'type' => 'required|in:add,subtract,set',
            'notes' => 'nullable|string|max:1000',
        ]);

        switch ($request->type) {
            case 'add':
                $material->incrementStock($request->quantity);
                break;
            case 'subtract':
                $material->decrementStock($request->quantity);
                break;
            case 'set':
                $material->current_stock = max(0, $request->quantity);
                $material->save();
                break;
        }

        return response()->json([
            'message' => 'Material stock adjusted successfully',
            'data' => $material->fresh(),
        ]);
    }

    /**
     * Get low stock materials
     */
    public function lowStock(Request $request)
    {
        $materials = Material::whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->orderBy('current_stock', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $materials->items(),
            'current_page' => $materials->currentPage(),
            'last_page' => $materials->lastPage(),
            'per_page' => $materials->perPage(),
            'total' => $materials->total(),
        ]);
    }
}
