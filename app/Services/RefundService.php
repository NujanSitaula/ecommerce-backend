<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class RefundService
{
    protected TransactionService $transactionService;
    protected StripeClient $stripe;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a refund request transaction
     */
    public function createRefundRequest(Order $order, float $amount, string $description, array $metadata = [], ?User $createdBy = null): Transaction
    {
        $transaction = $this->transactionService->createTransaction([
            'order_id' => $order->id,
            'type' => 'refund_request',
            'status' => 'pending',
            'amount' => $amount,
            'currency' => 'USD',
            'description' => $description,
            'metadata' => $metadata,
            'created_by' => $createdBy?->id ?? auth()->id(),
        ]);

        Log::info("Refund request created: Transaction {$transaction->id} for Order {$order->id}");

        return $transaction;
    }

    /**
     * Approve a refund request
     */
    public function approveRefund(Transaction $transaction, ?User $approvedBy = null): void
    {
        if ($transaction->type !== 'refund_request') {
            throw new \Exception('Transaction is not a refund request');
        }

        if ($transaction->status !== 'pending') {
            throw new \Exception('Refund request is not pending');
        }

        $this->transactionService->updateTransactionStatus($transaction, 'approved', $approvedBy ?? auth()->user());

        Log::info("Refund request approved: Transaction {$transaction->id}");
    }

    /**
     * Process an approved refund via Stripe
     */
    public function processRefund(Transaction $transaction, Order $order): void
    {
        if ($transaction->type !== 'refund_request') {
            throw new \Exception('Transaction is not a refund request');
        }

        if ($transaction->status !== 'approved') {
            throw new \Exception('Refund request must be approved before processing');
        }

        if (!$order->stripe_payment_intent_id) {
            throw new \Exception('Order does not have a Stripe payment intent ID');
        }

        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $order->stripe_payment_intent_id,
                'amount' => (int) ($transaction->amount * 100), // Convert to cents
                'reason' => 'requested_by_customer',
            ]);

            $transaction->stripe_refund_id = $refund->id;
            $this->transactionService->updateTransactionStatus($transaction, 'completed');

            Log::info("Refund processed: Transaction {$transaction->id}, Stripe Refund {$refund->id}");
        } catch (ApiErrorException $e) {
            $this->transactionService->updateTransactionStatus($transaction, 'failed');
            Log::error("Refund processing failed: Transaction {$transaction->id}, Error: {$e->getMessage()}");
            throw new \Exception('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Reject a refund request
     */
    public function rejectRefund(Transaction $transaction, ?User $rejectedBy = null): void
    {
        if ($transaction->type !== 'refund_request') {
            throw new \Exception('Transaction is not a refund request');
        }

        if ($transaction->status !== 'pending') {
            throw new \Exception('Refund request is not pending');
        }

        $this->transactionService->updateTransactionStatus($transaction, 'rejected', $rejectedBy ?? auth()->user());

        Log::info("Refund request rejected: Transaction {$transaction->id}");
    }
}

