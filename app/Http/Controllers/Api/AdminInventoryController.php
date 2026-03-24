<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {
    }

    /**
     * List all products with inventory status
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'variants']);

        // Filters
        if ($request->has('low_stock')) {
            $query->whereColumn('quantity', '<=', 'low_stock_threshold')
                  ->where('track_inventory', true);
        }

        if ($request->has('inventory_type')) {
            $query->where('inventory_type', $request->inventory_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

    /**
     * Get inventory details and history for a product
     */
    public function show($productId)
    {
        $product = Product::with(['category', 'variants', 'materials'])->findOrFail($productId);
        
        $history = $this->inventoryService->getInventoryHistory($product, null, 50);
        $isLowStock = $this->inventoryService->checkLowStock($product);

        return response()->json([
            'product' => $product,
            'history' => $history,
            'is_low_stock' => $isLowStock,
            'available_quantity' => $product->getAvailableQuantity(),
        ]);
    }

    /**
     * Manual inventory adjustment
     */
    public function adjust(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $product = Product::findOrFail($productId);

        $this->inventoryService->adjustInventory(
            $product,
            $request->quantity,
            $request->variant_id,
            $request->notes
        );

        return response()->json([
            'message' => 'Inventory adjusted successfully',
            'product' => $product->fresh(),
        ]);
    }

    /**
     * Get low stock alerts
     */
    public function lowStock(Request $request)
    {
        $products = Product::where('track_inventory', true)
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->with(['category'])
            ->orderBy('quantity', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

    /**
     * Get all inventory transactions with filters
     */
    public function transactions(Request $request)
    {
        $query = InventoryTransaction::with(['product', 'variant', 'order', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('variant_id')) {
            $query->where('variant_id', $request->variant_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'data' => $transactions->items(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
        ]);
    }
}
