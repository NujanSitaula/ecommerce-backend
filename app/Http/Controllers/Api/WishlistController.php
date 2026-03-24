<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $productIds = Wishlist::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return response()->json([]);
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('status', 'active')
            ->get()
            ->sortBy(function (Product $product) use ($productIds) {
                return $productIds->search($product->id);
            })
            ->values();

        return ProductResource::collection($products);
    }

    public function ids()
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $ids = Wishlist::where('user_id', $userId)
            ->pluck('product_id')
            ->map(fn ($id) => (string) $id)
            ->values();

        return response()->json(['data' => $ids]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $item = Wishlist::firstOrCreate([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
        ]);

        return response()->json([
            'data' => [
                'id' => $item->id,
                'product_id' => (string) $item->product_id,
            ],
        ], 201);
    }

    public function destroy($productId)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
