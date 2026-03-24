<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPersonalizationResource extends JsonResource
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
            'order_item_id' => $this->order_item_id,
            'personalization_option_id' => $this->personalization_option_id,
            'value' => $this->value,
            'file_url' => $this->file_url,
            'personalization_option' => $this->whenLoaded('personalizationOption', function () {
                return [
                    'id' => $this->personalizationOption->id,
                    'name' => $this->personalizationOption->name,
                    'type' => $this->personalizationOption->type,
                ];
            }),
        ];
    }
}
