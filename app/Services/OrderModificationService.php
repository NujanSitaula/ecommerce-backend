<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderModification;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class OrderModificationService
{
    protected TransactionService $transactionService;
    protected RefundService $refundService;
    protected \App\Services\InventoryService $inventoryService;
    protected StripeClient $stripe;

    public function __construct(
        TransactionService $transactionService,
        RefundService $refundService,
        \App\Services\InventoryService $inventoryService
    ) {
        $this->transactionService = $transactionService;
        $this->refundService = $refundService;
        $this->inventoryService = $inventoryService;
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Add item to existing order
     */
    public function addItemToOrder(
        Order $order,
        int $productId,
        int $quantity,
        ?int $variantId = null,
        ?User $createdBy = null
    ): OrderItem {
        if (!$order->canBeModified()) {
            throw new \Exception('Order cannot be modified. Only pending, confirmed, or processing orders can be modified.');
        }

        $product = Product::findOrFail($productId);
        $price = $product->sale_price ?? $product->price;
        $subtotal = $price * $quantity;

        DB::beginTransaction();
        try {
            // Create order item
            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
                'is_made_to_order' => $product->isMadeToOrder(),
                'production_status' => $product->isMadeToOrder() ? 'pending' : null,
            ]);

            // Handle inventory if not made-to-order
            if (!$product->isMadeToOrder() && $product->track_inventory) {
                $this->inventoryService->decrementInventory($product, $quantity, $variantId, $order->id);
            }

            // Create modification record
            $modification = OrderModification::create([
                'order_id' => $order->id,
                'modification_type' => 'item_added',
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
                'created_by' => $createdBy?->id ?? auth()->id(),
            ]);

            // Create transaction for the addition
            $transaction = $this->transactionService->createTransaction([
                'order_id' => $order->id,
                'type' => 'order_modification',
                'status' => 'completed',
                'amount' => $subtotal,
                'currency' => 'USD',
                'description' => "Item added: {$product->name} (x{$quantity})",
                'metadata' => [
                    'modification_id' => $modification->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ],
                'created_by' => $createdBy?->id ?? auth()->id(),
                'processed_at' => now(),
            ]);

            $modification->transaction_id = $transaction->id;
            $modification->save();

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            // Charge additional amount via Stripe
            if ($order->stripe_payment_intent_id) {
                try {
                    $this->stripe->paymentIntents->create([
                        'amount' => (int) ($subtotal * 100),
                        'currency' => 'usd',
                        'payment_method' => $order->paymentMethod->stripe_payment_method_id ?? null,
                        'customer' => $order->paymentMethod->stripe_customer_id ?? null,
                        'confirm' => true,
                    ]);
                } catch (ApiErrorException $e) {
                    Log::warning("Failed to charge additional amount for order {$order->id}: {$e->getMessage()}");
                    // Don't fail the operation, but log the issue
                }
            }

            DB::commit();

            Log::info("Item added to order: Order {$order->id}, Product {$product->id}, Quantity {$quantity}");

            return $orderItem->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove item from order (creates refund request)
     */
    public function removeItemFromOrder(
        Order $order,
        int $itemId,
        ?User $createdBy = null
    ): Transaction {
        if (!$order->canBeModified()) {
            throw new \Exception('Order cannot be modified. Only pending, confirmed, or processing orders can be modified.');
        }

        $orderItem = OrderItem::where('order_id', $order->id)
            ->where('id', $itemId)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Create modification record
            $modification = OrderModification::create([
                'order_id' => $order->id,
                'modification_type' => 'item_removed',
                'order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'quantity' => $orderItem->quantity,
                'price' => $orderItem->price,
                'subtotal' => $orderItem->subtotal,
                'created_by' => $createdBy?->id ?? auth()->id(),
            ]);

            // Create refund request
            $transaction = $this->refundService->createRefundRequest(
                $order,
                (float) $orderItem->subtotal,
                "Item removed: {$orderItem->product_name} (x{$orderItem->quantity})",
                [
                    'modification_id' => $modification->id,
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                ],
                $createdBy
            );

            $modification->transaction_id = $transaction->id;
            $modification->save();

            // Restore inventory if not made-to-order
            if ($orderItem->product && !$orderItem->is_made_to_order && $orderItem->product->track_inventory) {
                $orderItem->load('product');
                $this->inventoryService->incrementInventory(
                    $orderItem->product,
                    $orderItem->quantity,
                    $orderItem->variant_id,
                    'return',
                    'Order item removed'
                );
            }

            // Delete the order item
            $orderItem->delete();

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            DB::commit();

            Log::info("Item removed from order: Order {$order->id}, Item {$itemId}");

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Recalculate order totals after modifications
     */
    public function recalculateOrderTotals(Order $order): void
    {
        $order->load('items');

        // Recalculate subtotal from remaining items
        $subtotal = $order->items->sum('subtotal');

        // Keep existing shipping and discount amounts (could be enhanced to recalculate)
        $shippingFee = $order->shipping_fee;
        $discountAmount = $order->discount_amount ?? 0;
        $shippingDiscount = $order->shipping_discount ?? 0;

        $finalShippingFee = max(0.0, $shippingFee - $shippingDiscount);
        $total = max(0.0, $subtotal - $discountAmount + $finalShippingFee);

        $order->subtotal = $subtotal;
        $order->total = $total;
        $order->save();
    }

    /**
     * Get modification history for an order
     */
    public function getModificationHistory(Order $order)
    {
        return OrderModification::where('order_id', $order->id)
            ->with(['product', 'orderItem', 'transaction', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

