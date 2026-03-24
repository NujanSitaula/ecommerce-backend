<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusUpdatedMail;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TransactionResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InvoicePdfService;
use App\Services\ProductionService;
use App\Services\TransactionService;
use App\Services\RefundService;
use App\Services\OrderModificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminOrderController extends Controller
{
    public function __construct(
        protected ProductionService $productionService,
        protected TransactionService $transactionService,
        protected RefundService $refundService,
        protected OrderModificationService $orderModificationService,
        protected InvoicePdfService $invoicePdfService
    ) {
    }

    /**
     * List all orders with filters
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'items.personalizations.personalizationOption',
            'items.product.materials',
            'items.variant',
            'address.country',
            'address.state',
            'contactNumber',
            'paymentMethod',
            'user',
            'coupon',
        ]);

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Production status filter (for made-to-order items)
        // If production_status parameter exists (even if empty), filter for made-to-order items
        // This indicates the request is from the production management page
        if ($request->has('production_status')) {
            $productionStatus = $request->production_status;
            if ($productionStatus === '' || $productionStatus === 'all') {
                // Return all orders with made-to-order items (no status filter)
                $query->whereHas('items', function ($q) {
                    $q->where('is_made_to_order', true);
                });
            } else {
                // Filter by specific production status
                $query->whereHas('items', function ($q) use ($productionStatus) {
                    $q->where('is_made_to_order', true)
                      ->where('production_status', $productionStatus);
                });
            }
        }
        // If production_status is not provided, don't filter by production
        // (This is for regular order listing, not production management)

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search filter (order ID, customer name/email)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->get('per_page', 20);
        $orders = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);
    }

    /**
     * Download order invoice
     */
    public function downloadInvoice($id)
    {
        $order = Order::findOrFail($id);

        if ($order->isCancelled()) {
            return response()->json(['message' => 'Invoice not available for cancelled orders.'], 404);
        }

        $pdf = $this->invoicePdfService->generate($order);
        $filename = 'invoice-' . $order->id . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get order details
     */
    public function show($id)
    {
        $order = Order::with([
            'items.personalizations.personalizationOption',
            'items.product',
            'items.variant',
            'address.country',
            'address.state',
            'contactNumber',
            'paymentMethod',
            'user',
            'coupon',
        ])->findOrFail($id);

        return new OrderResource($order);
    }

    /**
     * Update order status
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        $recipientEmail = $order->guest_email ?? ($order->user?->email ?? null);
        $customerName = $order->guest_name ?? ($order->user?->name ?? 'there');

        if (!empty($recipientEmail)) {
            Mail::to($recipientEmail)->queue(
                new OrderStatusUpdatedMail($order, $order->status, null, $customerName),
            );
        }

        return new OrderResource($order->load([
            'items.personalizations.personalizationOption',
            'items.product',
            'items.variant',
            'address',
            'contactNumber',
            'paymentMethod',
            'user',
            'coupon',
        ]));
    }

    /**
     * Update production status for a specific order item
     */
    public function updateProductionStatus(Request $request, $orderId, $itemId)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $orderId)
            ->where('id', $itemId)
            ->firstOrFail();

        if (!$item->is_made_to_order) {
            return response()->json([
                'message' => 'This item is not a made-to-order item',
            ], 422);
        }

        $this->productionService->updateProductionStatus($item, $request->status);

        return new OrderResource($order->fresh()->load([
            'items.personalizations.personalizationOption',
            'items.product',
            'items.variant',
        ]));
    }

    /**
     * Start production for an order item
     */
    public function startProduction($orderId, $itemId)
    {
        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $orderId)
            ->where('id', $itemId)
            ->firstOrFail();

        if (!$item->is_made_to_order) {
            return response()->json([
                'message' => 'This item is not a made-to-order item',
            ], 422);
        }

        $this->productionService->startProduction($item);

        return new OrderResource($order->fresh()->load([
            'items.personalizations.personalizationOption',
            'items.product',
            'items.variant',
        ]));
    }

    /**
     * Complete production for an order item
     */
    public function completeProduction($orderId, $itemId)
    {
        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $orderId)
            ->where('id', $itemId)
            ->firstOrFail();

        if (!$item->is_made_to_order) {
            return response()->json([
                'message' => 'This item is not a made-to-order item',
            ], 422);
        }

        $this->productionService->completeProduction($item);

        return new OrderResource($order->fresh()->load([
            'items.personalizations.personalizationOption',
            'items.product',
            'items.variant',
        ]));
    }

    /**
     * Add item to order
     */
    public function addItem(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $order = Order::findOrFail($id);

        $orderItem = $this->orderModificationService->addItemToOrder(
            $order,
            $request->product_id,
            $request->quantity,
            $request->variant_id,
            auth()->user()
        );

        return new OrderResource($order->fresh()->load([
            'items.personalizations.personalizationOption',
            'items.product.materials',
            'items.variant',
            'transactions',
            'modifications',
        ]));
    }

    /**
     * Remove item from order (creates refund request)
     */
    public function removeItem(Request $request, $id, $itemId)
    {
        $order = Order::findOrFail($id);

        $transaction = $this->orderModificationService->removeItemFromOrder(
            $order,
            $itemId,
            auth()->user()
        );

        return response()->json([
            'message' => 'Item removed and refund request created',
            'transaction' => new TransactionResource($transaction->load(['order', 'createdBy'])),
            'order' => new OrderResource($order->fresh()->load([
                'items.personalizations.personalizationOption',
                'items.product.materials',
                'items.variant',
                'transactions',
                'modifications',
            ])),
        ]);
    }

    /**
     * Get order modifications
     */
    public function modifications($id)
    {
        $order = Order::findOrFail($id);
        $modifications = $this->orderModificationService->getModificationHistory($order);

        return response()->json([
            'data' => $modifications->map(function ($mod) {
                return [
                    'id' => $mod->id,
                    'modification_type' => $mod->modification_type,
                    'quantity' => $mod->quantity,
                    'price' => (float) $mod->price,
                    'subtotal' => (float) $mod->subtotal,
                    'notes' => $mod->notes,
                    'product' => $mod->product ? [
                        'id' => $mod->product->id,
                        'name' => $mod->product->name,
                    ] : null,
                    'created_by' => $mod->createdBy ? [
                        'id' => $mod->createdBy->id,
                        'name' => $mod->createdBy->name,
                    ] : null,
                    'created_at' => $mod->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Get order transactions
     */
    public function transactions($id)
    {
        $order = Order::findOrFail($id);
        $transactions = $order->transactions()->with(['createdBy', 'approvedBy'])->get();

        return response()->json([
            'data' => TransactionResource::collection($transactions),
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $order = Order::with('items')->findOrFail($id);

        if ($order->status === 'delivered') {
            return response()->json([
                'message' => 'Cannot cancel a delivered order',
            ], 422);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'message' => 'Order is already cancelled',
            ], 422);
        }

        // Create cancellation transaction
        $transaction = $this->transactionService->createTransaction([
            'order_id' => $order->id,
            'type' => 'adjustment',
            'status' => 'completed',
            'amount' => 0,
            'currency' => 'USD',
            'description' => 'Order cancelled',
            'metadata' => [
                'cancellation_reason' => $request->reason,
            ],
            'created_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        // Create refund requests for all items if order was paid
        if ($order->stripe_payment_intent_id && $order->total > 0) {
            $order->load('items');
            foreach ($order->items as $item) {
                $this->refundService->createRefundRequest(
                    $order,
                    (float) $item->subtotal,
                    "Order cancellation: {$item->product_name}",
                    [
                        'order_item_id' => $item->id,
                        'cancellation' => true,
                    ],
                    auth()->user()
                );
            }
        }

        $order->status = 'cancelled';
        $order->cancelled_at = now();
        $order->cancellation_reason = $request->reason;
        $order->save();

        $order->loadMissing(['user']);

        $recipientEmail = $order->guest_email ?? ($order->user?->email ?? null);
        $customerName = $order->guest_name ?? ($order->user?->name ?? 'there');

        if (!empty($recipientEmail)) {
            Mail::to($recipientEmail)->queue(
                new OrderStatusUpdatedMail(
                    $order,
                    'cancelled',
                    $order->cancellation_reason,
                    $customerName,
                ),
            );
        }

        return new OrderResource($order->fresh()->load([
            'items.personalizations.personalizationOption',
            'items.product.materials',
            'items.variant',
            'transactions',
            'modifications',
        ]));
    }
}
