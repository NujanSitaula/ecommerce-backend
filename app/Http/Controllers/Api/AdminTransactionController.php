<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {
    }

    /**
     * List all transactions with filters
     */
    public function index(Request $request)
    {
        $query = \App\Models\Transaction::with(['order', 'createdBy', 'approvedBy']);

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->get('per_page', 20);
        $transactions = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => TransactionResource::collection($transactions->items()),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
        ]);
    }

    /**
     * Get transaction details
     */
    public function show($id)
    {
        $transaction = \App\Models\Transaction::with(['order', 'createdBy', 'approvedBy', 'orderModifications'])
            ->findOrFail($id);

        return new TransactionResource($transaction);
    }

    /**
     * Get financial reconciliation summary
     */
    public function reconciliation(Request $request)
    {
        $query = \App\Models\Transaction::query();

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $totalPayments = (clone $query)->where('type', 'payment')
            ->where('status', 'completed')
            ->sum('amount');

        // Count refunds: completed refund_request transactions are actual refunds
        $totalRefunds = (clone $query)->where('type', 'refund_request')
            ->where('status', 'completed')
            ->sum('amount');

        $pendingRefunds = (clone $query)->where('type', 'refund_request')
            ->where('status', 'pending')
            ->sum('amount');

        $approvedRefunds = (clone $query)->where('type', 'refund_request')
            ->where('status', 'approved')
            ->sum('amount');

        return response()->json([
            'total_payments' => (float) $totalPayments,
            'total_refunds' => (float) $totalRefunds,
            'pending_refunds' => (float) $pendingRefunds,
            'approved_refunds' => (float) $approvedRefunds,
            'net_amount' => (float) ($totalPayments - $totalRefunds - $pendingRefunds - $approvedRefunds),
        ]);
    }
}
