<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\FlashSale;
use Illuminate\Http\JsonResponse;

class FlashSaleController extends Controller
{
    /**
     * Get the currently active flash sale.
     */
    public function show(): JsonResponse
    {
        $flashSale = FlashSale::active()->with('products')->first();

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
                'products' => ProductResource::collection($flashSale->products),
            ],
        ]);
    }
}
