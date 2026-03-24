<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'average_rating' => $this->whenLoaded('approvedReviews', function () {
                $avg = $this->approvedReviews->avg('rating');
                return $avg !== null ? (float) round((float) $avg, 2) : 0;
            }) ?? 0,
            'review_count' => $this->whenLoaded('approvedReviews', function () {
                return (int) $this->approvedReviews->count();
            }) ?? 0,
            'thumbnail_url' => $this->thumbnail_url,
            'original_url' => $this->original_url,
            'gallery' => $this->gallery ?? [],
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'currency' => $this->currency ?? 'USD',
            'unit' => $this->unit,
            'tags' => $this->tags ?? [],
            'type' => $this->type,
            'status' => $this->status,
            'featured' => $this->featured,
            'stock' => $this->quantity,
            'sku' => $this->sku,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'shipping_class' => $this->shipping_class,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'category_id' => $this->category_id,
            // New Etsy features
            'inventory_type' => $this->inventory_type ?? 'in_stock',
            'production_time_days' => $this->production_time_days,
            'min_quantity' => $this->min_quantity,
            'max_quantity' => $this->max_quantity,
            'low_stock_threshold' => $this->low_stock_threshold ?? 5,
            'track_inventory' => $this->track_inventory ?? true,
            'cost_of_goods' => $this->cost_of_goods ? (float) $this->cost_of_goods : null,
            'materials_required' => $this->materials_required ?? [],
            'available_quantity' => $this->getAvailableQuantity(),
            'is_low_stock' => $this->track_inventory && $this->quantity <= ($this->low_stock_threshold ?? 5),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'variants' => $this->whenLoaded('variants', function () {
                return $this->variants->map(function ($variant) {
                    return [
                        'id' => (string) $variant->id,
                        'product_id' => (string) $variant->product_id,
                        'sku' => $variant->sku,
                        'attributes' => $variant->attributes,
                        'price' => $variant->price ? (float) $variant->price : null,
                        'sale_price' => $variant->sale_price ? (float) $variant->sale_price : null,
                        'quantity' => $variant->quantity,
                        'image_url' => $variant->image_url,
                    ];
                });
            }),
            'variations' => $this->whenLoaded('variants', function () {
                $variants = $this->variants;
                if ($variants->isEmpty()) {
                    return [];
                }

                // Extract all unique attribute keys and their values
                $attributeMap = [];
                foreach ($variants as $variant) {
                    if (is_array($variant->attributes)) {
                        foreach ($variant->attributes as $key => $value) {
                            $slug = str_replace('_', '-', strtolower($key));
                            $name = ucwords(str_replace('_', ' ', $key));
                            if (!isset($attributeMap[$slug])) {
                                $attributeMap[$slug] = [
                                    'name' => $name,
                                    'values' => [],
                                ];
                            }
                            if (!in_array($value, $attributeMap[$slug]['values'])) {
                                $attributeMap[$slug]['values'][] = $value;
                            }
                        }
                    }
                }

                // Build variations array
                $variations = [];
                $attributeId = 1;
                foreach ($attributeMap as $slug => $attrData) {
                    $valueId = 1;
                    foreach ($attrData['values'] as $value) {
                        $variations[] = [
                            'id' => $attributeId * 1000 + $valueId,
                            'attribute_id' => $attributeId,
                            'value' => $value,
                            'attribute' => [
                                'id' => $attributeId,
                                'slug' => $slug,
                                'name' => $attrData['name'],
                                'values' => array_map(function ($val, $idx) use ($attributeId) {
                                    return [
                                        'id' => $attributeId * 1000 + $idx + 1,
                                        'attribute_id' => $attributeId,
                                        'value' => $val,
                                    ];
                                }, $attrData['values'], array_keys($attrData['values'])),
                            ],
                        ];
                        $valueId++;
                    }
                    $attributeId++;
                }

                return $variations;
            }),
            'variation_options' => $this->whenLoaded('variants', function () {
                $variants = $this->variants;
                if ($variants->isEmpty()) {
                    return [];
                }

                return $variants->map(function ($variant, $index) {
                    $attributes = is_array($variant->attributes) ? $variant->attributes : [];
                    $title = !empty($attributes) ? implode(' / ', array_values($attributes)) : 'Default';
                    $options = [];
                    foreach ($attributes as $key => $value) {
                        $options[] = [
                            'name' => ucwords(str_replace('_', ' ', $key)),
                            'value' => $value,
                        ];
                    }

                    return [
                        'id' => (string) $variant->id,
                        'title' => $title,
                        'price' => $variant->price ? (float) $variant->price : (float) $this->price,
                        'sale_price' => $variant->sale_price ? (float) $variant->sale_price : null,
                        'quantity' => (string) $variant->quantity,
                        'is_disable' => $variant->quantity <= 0 ? 1 : 0,
                        'sku' => $variant->sku,
                        'options' => $options,
                    ];
                })->toArray();
            }),
            'min_price' => $this->whenLoaded('variants', function () {
                $prices = $this->variants->map(function ($v) {
                    return $v->sale_price && $v->sale_price > 0
                        ? (float) $v->sale_price
                        : ($v->price ? (float) $v->price : (float) $this->price);
                });
                return $prices->isNotEmpty() ? $prices->min() : (float) $this->price;
            }, (float) $this->price),
            'max_price' => $this->whenLoaded('variants', function () {
                $prices = $this->variants->map(function ($v) {
                    return $v->price ? (float) $v->price : (float) $this->price;
                });
                return $prices->isNotEmpty() ? $prices->max() : (float) $this->price;
            }, (float) $this->price),
            'personalization_options' => $this->whenLoaded('personalizationOptions', function () {
                return $this->personalizationOptions->map(function ($option) {
                    return [
                        'id' => (string) $option->id,
                        'name' => $option->name,
                        'type' => $option->type,
                        'required' => $option->required,
                        'options' => $option->options ?? [],
                        'max_length' => $option->max_length,
                        'price_adjustment' => $option->price_adjustment ? (float) $option->price_adjustment : null,
                        'order' => $option->order,
                    ];
                });
            }),
            'materials' => $this->whenLoaded('materials', function () {
                return $this->materials->map(function ($material) {
                    return [
                        'id' => (string) $material->id,
                        'name' => $material->name,
                        'description' => $material->description,
                        'unit' => $material->unit,
                        'current_stock' => (float) $material->current_stock,
                        'low_stock_threshold' => (float) $material->low_stock_threshold,
                        'cost_per_unit' => (float) $material->cost_per_unit,
                        'supplier' => $material->supplier,
                        'quantity_required' => (float) $material->pivot->quantity_required,
                    ];
                });
            }),
            'seo' => $this->whenLoaded('seo', function () {
                return [
                    'seo_title' => $this->seo->seo_title,
                    'seo_description' => $this->seo->seo_description,
                    'seo_keywords' => $this->seo->seo_keywords,
                    'canonical_url' => $this->seo->canonical_url,
                    'meta_robots' => $this->seo->meta_robots,
                    'og_title' => $this->seo->og_title,
                    'og_description' => $this->seo->og_description,
                    'og_image_url' => $this->seo->og_image_url,
                    'og_type' => $this->seo->og_type,
                    'og_url_override' => $this->seo->og_url_override,
                    'twitter_title' => $this->seo->twitter_title,
                    'twitter_description' => $this->seo->twitter_description,
                    'twitter_image_url' => $this->seo->twitter_image_url,
                    'twitter_card_type' => $this->seo->twitter_card_type,
                    'seo_status' => $this->seo->seo_status,
                    'seo_score' => $this->seo->seo_score,
                ];
            }),
        ];
    }
}












