<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPersonalizationOption;
use Illuminate\Http\Request;

class AdminPersonalizationController extends Controller
{
    /**
     * Get personalization options for a product
     */
    public function index($productId)
    {
        $product = Product::findOrFail($productId);
        $options = $product->personalizationOptions;

        return response()->json([
            'data' => $options,
        ]);
    }

    /**
     * Create a personalization option
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,select,color,file_upload,checkbox',
            'required' => 'boolean',
            'options' => 'nullable|array',
            'max_length' => 'nullable|integer|min:1',
            'price_adjustment' => 'nullable|numeric|min:0',
            'order' => 'nullable|integer|min:0',
        ]);

        $option = ProductPersonalizationOption::create([
            'product_id' => $product->id,
            'name' => $request->name,
            'type' => $request->type,
            'required' => $request->boolean('required', false),
            'options' => $request->options,
            'max_length' => $request->max_length,
            'price_adjustment' => $request->price_adjustment,
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'message' => 'Personalization option created successfully',
            'data' => $option,
        ], 201);
    }

    /**
     * Update a personalization option
     */
    public function update(Request $request, $id)
    {
        $option = ProductPersonalizationOption::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:text,number,select,color,file_upload,checkbox',
            'required' => 'sometimes|boolean',
            'options' => 'nullable|array',
            'max_length' => 'nullable|integer|min:1',
            'price_adjustment' => 'nullable|numeric|min:0',
            'order' => 'nullable|integer|min:0',
        ]);

        $option->update($request->only([
            'name', 'type', 'required', 'options', 'max_length', 'price_adjustment', 'order'
        ]));

        return response()->json([
            'message' => 'Personalization option updated successfully',
            'data' => $option->fresh(),
        ]);
    }

    /**
     * Delete a personalization option
     */
    public function destroy($id)
    {
        $option = ProductPersonalizationOption::findOrFail($id);
        $option->delete();

        return response()->json([
            'message' => 'Personalization option deleted successfully',
        ]);
    }
}
