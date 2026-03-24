<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Create a transaction record
     */
    public function createTransaction(array $data): Transaction
    {
        return Transaction::create($data);
    }

    /**
     * Get transactions for an order
     */
    public function getOrderTransactions(Order $order)
    {
        return Transaction::where('order_id', $order->id)
            ->with(['createdBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transactions by type
     */
    public function getTransactionsByType(string $type, string $status = null)
    {
        $query = Transaction::where('type', $type)
            ->with(['order', 'createdBy', 'approvedBy']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus(Transaction $transaction, string $status, ?User $approvedBy = null): void
    {
        $transaction->status = $status;

        if ($status === 'approved' && $approvedBy) {
            $transaction->approved_by = $approvedBy->id;
            $transaction->approved_at = now();
        }

        if ($status === 'completed') {
            $transaction->processed_at = now();
        }

        $transaction->save();

        Log::info("Transaction {$transaction->id} status updated to {$status}");
    }

    /**
     * Get pending refund requests
     */
    public function getPendingRefundRequests()
    {
        return Transaction::where('type', 'refund_request')
            ->where('status', 'pending')
            ->with(['order', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

