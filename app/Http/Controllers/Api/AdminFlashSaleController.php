<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminFlashSaleController extends Controller
{
    /**
     * Get the current flash sale configuration.
     */
    public function getCurrent(): JsonResponse
    {
        $flashSale = FlashSale::with('products')->latest()->first();

        if (!$flashSale) {
            return response()->json([
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $flashSale->id,
                'title' => $flashSale->title,
                'starts_at' => $flashSale->starts_at->toISOString(),
                'ends_at' => $flashSale->ends_at->toISOString(),
                'is_active' => $flashSale->is_active,
                'products' => $flashSale->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'sort_order' => $product->pivot->sort_order,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Create or update the flash sale configuration.
     */
    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['exists:products,id'],
            'sort_orders' => ['nullable', 'array'],
            'sort_orders.*' => ['integer', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            // Get or create flash sale (only one active at a time)
            $flashSale = FlashSale::latest()->first();

            if (!$flashSale) {
                $flashSale = new FlashSale();
            }

            $flashSale->title = $validated['title'] ?? null;
            $flashSale->starts_at = $validated['starts_at'];
            $flashSale->ends_at = $validated['ends_at'];
            $flashSale->is_active = $validated['is_active'] ?? true;
            $flashSale->save();

            // Sync products with sort orders
            $productIds = $validated['product_ids'];
            $sortOrders = $validated['sort_orders'] ?? [];

            $syncData = [];
            foreach ($productIds as $index => $productId) {
                $syncData[$productId] = [
                    'sort_order' => $sortOrders[$index] ?? $index,
                ];
            }

            $flashSale->products()->sync($syncData);

            DB::commit();

            // Reload relationships
            $flashSale->load('products');

            return response()->json([
                'message' => 'Flash sale updated successfully',
                'data' => [
                    'id' => $flashSale->id,
                    'title' => $flashSale->title,
                    'starts_at' => $flashSale->starts_at->toISOString(),
                    'ends_at' => $flashSale->ends_at->toISOString(),
                    'is_active' => $flashSale->is_active,
                    'products' => $flashSale->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'sort_order' => $product->pivot->sort_order,
                        ];
                    }),
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update flash sale',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
