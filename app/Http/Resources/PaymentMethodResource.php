<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'brand' => $this->brand,
            'last4' => $this->last4,
            'cardholder_name' => $this->cardholder_name,
            'exp_month' => $this->exp_month,
            'exp_year' => $this->exp_year,
            'is_default' => (bool) $this->is_default,
        ];
    }
}


