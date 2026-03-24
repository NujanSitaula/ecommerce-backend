<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Decrement inventory for a product or variant
     */
    public function decrementInventory(
        Product $product,
        int $quantity,
        ?int $variantId = null,
        ?int $orderId = null
    ): void {
        if (!$product->track_inventory) {
            return;
        }

        $target = $variantId 
            ? ProductVariant::findOrFail($variantId)
            : $product;

        $previousQuantity = $target->quantity;

        if ($target->quantity < $quantity) {
            throw new \Exception("Insufficient inventory. Available: {$target->quantity}, Requested: {$quantity}");
        }

        $target->quantity = max(0, $target->quantity - $quantity);
        $target->save();

        // Create inventory transaction
        InventoryTransaction::create([
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'type' => 'sale',
            'quantity' => -$quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $target->quantity,
            'order_id' => $orderId,
            'user_id' => auth()->id(),
        ]);

        // Check for low stock
        $this->checkLowStock($product);
    }

    /**
     * Increment inventory for a product or variant
     */
    public function incrementInventory(
        Product $product,
        int $quantity,
        ?int $variantId = null,
        string $type = 'purchase',
        ?string $notes = null
    ): void {
        if (!$product->track_inventory) {
            return;
        }

        $target = $variantId 
            ? ProductVariant::findOrFail($variantId)
            : $product;

        $previousQuantity = $target->quantity;
        $target->quantity += $quantity;
        $target->save();

        // Create inventory transaction
        InventoryTransaction::create([
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'type' => $type,
            'quantity' => $quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $target->quantity,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Adjust inventory to a specific quantity
     */
    public function adjustInventory(
        Product $product,
        int $newQuantity,
        ?int $variantId = null,
        ?string $notes = null
    ): void {
        if (!$product->track_inventory) {
            return;
        }

        $target = $variantId 
            ? ProductVariant::findOrFail($variantId)
            : $product;

        $previousQuantity = $target->quantity;
        $difference = $newQuantity - $previousQuantity;

        $target->quantity = max(0, $newQuantity);
        $target->save();

        // Create inventory transaction
        InventoryTransaction::create([
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'type' => 'adjustment',
            'quantity' => $difference,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $target->quantity,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        // Check for low stock
        $this->checkLowStock($product);
    }

    /**
     * Get inventory history for a product or variant
     */
    public function getInventoryHistory(
        Product $product,
        ?int $variantId = null,
        int $limit = 50
    ) {
        $query = InventoryTransaction::where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        return $query->get();
    }

    /**
     * Check if product is low on stock
     */
    public function checkLowStock(Product $product): bool
    {
        if (!$product->track_inventory) {
            return false;
        }

        $isLow = $product->quantity <= $product->low_stock_threshold;

        if ($isLow) {
            Log::warning("Low stock alert for product {$product->id}: {$product->name}. Current: {$product->quantity}, Threshold: {$product->low_stock_threshold}");
            // TODO: Send notification to admins
        }

        return $isLow;
    }

    /**
     * Reserve inventory for made-to-order items
     */
    public function reserveInventory(
        Product $product,
        int $quantity,
        ?int $variantId = null
    ): bool {
        if (!$product->track_inventory) {
            return true;
        }

        $target = $variantId 
            ? ProductVariant::findOrFail($variantId)
            : $product;

        // For made-to-order, we might want to reserve materials instead
        // For now, just check availability
        return $target->quantity >= $quantity;
    }
}

