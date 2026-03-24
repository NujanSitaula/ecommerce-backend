<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'stripe_refund_id' => $this->stripe_refund_id,
            'description' => $this->description,
            'metadata' => $this->metadata ?? [],
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                    'email' => $this->approvedBy->email,
                ];
            }),
            'approved_at' => $this->approved_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'total' => (float) $this->order->total,
                    'status' => $this->order->status,
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
