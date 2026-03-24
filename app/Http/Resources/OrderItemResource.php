<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_slug' => $this->product_slug,
            'quantity' => (int) $this->quantity,
            'price' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            'variant_id' => $this->variant_id,
            'variant' => $this->whenLoaded('variant', function () {
                return [
                    'id' => $this->variant->id,
                    'sku' => $this->variant->sku,
                    'attributes' => $this->variant->attributes,
                ];
            }),
            'product' => $this->whenLoaded('product', function () {
                $productData = [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                    'thumbnail_url' => $this->product->thumbnail_url,
                ];
                
                // Include materials if loaded
                if ($this->product->relationLoaded('materials')) {
                    $productData['materials'] = $this->product->materials->map(function ($material) {
                        return [
                            'id' => $material->id,
                            'name' => $material->name,
                            'unit' => $material->unit,
                            'current_stock' => (float) $material->current_stock,
                            'low_stock_threshold' => (float) $material->low_stock_threshold,
                            'quantity_required' => (float) $material->pivot->quantity_required,
                        ];
                    });
                }
                
                return $productData;
            }),
            // Production fields
            'is_made_to_order' => (bool) $this->is_made_to_order,
            'production_status' => $this->production_status,
            'production_started_at' => $this->production_started_at?->toISOString(),
            'production_completed_at' => $this->production_completed_at?->toISOString(),
            'estimated_completion_date' => $this->estimated_completion_date?->format('Y-m-d'),
            'personalizations' => OrderPersonalizationResource::collection($this->whenLoaded('personalizations')),
        ];
    }
}
