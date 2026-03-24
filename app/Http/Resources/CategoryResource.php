<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'parent_id' => $this->parent_id,
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
            'image' => [
                'id' => $this->id,
                'thumbnail' => $this->image_thumbnail ?? $this->thumbnail_url ?? null,
                'original' => $this->image_original ?? $this->original_url ?? null,
            ],
            'icon' => $this->icon ?? null,
            'children' => [], // placeholder for compatibility with frontend shape
        ];
    }
}


