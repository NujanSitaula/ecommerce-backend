<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'product_id' => (string) $this->product_id,
            'user_id' => (string) $this->user_id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => (string) $this->product?->id,
                    'name' => $this->product?->name,
                    'slug' => $this->product?->slug,
                ];
            }),

            'rating' => (int) $this->rating,
            'title' => $this->title,
            'description' => $this->description,
            'author' => $this->author_name,
            'author_email' => $this->author_email,

            'status' => $this->status,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,

            'moderated_by' => $this->whenLoaded('moderatedBy', function () {
                return [
                    'id' => (string) $this->moderatedBy?->id,
                    'name' => $this->moderatedBy?->name,
                ];
            }),
            'moderated_at' => $this->moderated_at?->toISOString(),

            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

