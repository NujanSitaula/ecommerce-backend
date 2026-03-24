<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminRefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService
    ) {
    }

    /**
     * List pending and approved refund requests (that need action)
     */
    public function pending()
    {
        $refunds = Transaction::where('type', 'refund_request')
            ->whereIn('status', ['pending', 'approved'])
            ->with(['order', 'createdBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => TransactionResource::collection($refunds),
        ]);
    }

    /**
     * Approve a refund request
     */
    public function approve(Request $request, $orderId, $transactionId)
    {
        $order = Order::findOrFail($orderId);
        $transaction = Transaction::where('order_id', $orderId)
            ->where('id', $transactionId)
            ->firstOrFail();

        $this->refundService->approveRefund($transaction, Auth::user());

        return new TransactionResource($transaction->fresh()->load(['order', 'createdBy', 'approvedBy']));
    }

    /**
     * Reject a refund request
     */
    public function reject(Request $request, $orderId, $transactionId)
    {
        $order = Order::findOrFail($orderId);
        $transaction = Transaction::where('order_id', $orderId)
            ->where('id', $transactionId)
            ->firstOrFail();

        $this->refundService->rejectRefund($transaction, Auth::user());

        return new TransactionResource($transaction->fresh()->load(['order', 'createdBy', 'approvedBy']));
    }

    /**
     * Process an approved refund
     */
    public function process(Request $request, $orderId, $transactionId)
    {
        $order = Order::findOrFail($orderId);
        $transaction = Transaction::where('order_id', $orderId)
            ->where('id', $transactionId)
            ->firstOrFail();

        $this->refundService->processRefund($transaction, $order);

        return new TransactionResource($transaction->fresh()->load(['order', 'createdBy', 'approvedBy']));
    }
}
