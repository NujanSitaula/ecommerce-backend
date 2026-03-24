<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'user_id' => $this->user_id,
            'guest_email' => $this->guest_email,
            'guest_name' => $this->guest_name,
            'address' => $this->whenLoaded('address', function () {
                return [
                    'id' => $this->address->id,
                    'title' => $this->address->title,
                    'name' => $this->address->name,
                    'address_line1' => $this->address->address_line1,
                    'address_line2' => $this->address->address_line2,
                    'city' => $this->address->city,
                    'postal_code' => $this->address->postal_code,
                    'country' => $this->address->country?->name,
                    'state' => $this->address->state?->name,
                ];
            }),
            'contact_number' => $this->whenLoaded('contactNumber', function () {
                return [
                    'id' => $this->contactNumber->id,
                    'title' => $this->contactNumber->title,
                    'phone' => $this->contactNumber->phone,
                ];
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return [
                    'id' => $this->paymentMethod->id,
                    'brand' => $this->paymentMethod->brand,
                    'last4' => $this->paymentMethod->last4,
                    'cardholder_name' => $this->paymentMethod->cardholder_name,
                ];
            }),
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'delivery_date' => $this->delivery_date->format('Y-m-d'),
            'gift_wrapped' => (bool) $this->gift_wrapped,
            'delivery_instructions' => $this->delivery_instructions,
            'leave_at_door' => (bool) $this->leave_at_door,
            'subtotal' => (float) $this->subtotal,
            'shipping_fee' => (float) $this->shipping_fee,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'shipping_discount' => (float) ($this->shipping_discount ?? 0),
            'tax_amount' => (float) ($this->tax_amount ?? 0),
            'tax_rate' => (float) ($this->tax_rate ?? 0),
            'tax_type' => $this->tax_type,
            'total' => (float) $this->total,
            'status' => $this->status,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'coupon' => $this->whenLoaded('coupon', function () {
                return [
                    'id' => $this->coupon->id,
                    'code' => $this->coupon->code,
                    'type' => $this->coupon->type,
                    'value' => $this->coupon->value,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'transactions' => $this->whenLoaded('transactions', function () {
                return \App\Http\Resources\TransactionResource::collection($this->transactions);
            }),
            'modifications' => $this->whenLoaded('modifications', function () {
                return $this->modifications->map(function ($mod) {
                    return [
                        'id' => $mod->id,
                        'modification_type' => $mod->modification_type,
                        'quantity' => $mod->quantity,
                        'price' => (float) $mod->price,
                        'subtotal' => (float) $mod->subtotal,
                        'created_at' => $mod->created_at->toISOString(),
                    ];
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
