<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'meta_title',
        'meta_description',
        'price',
        'sale_price',
        'currency',
        'quantity',
        'unit',
        'weight',
        'length',
        'width',
        'height',
        'shipping_class',
        'type',
        'featured',
        'status',
        'thumbnail_url',
        'original_url',
        'gallery',
        'tags',
        // New Etsy features
        'inventory_type',
        'production_time_days',
        'min_quantity',
        'max_quantity',
        'low_stock_threshold',
        'track_inventory',
        'cost_of_goods',
        'materials_required',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'gallery' => 'array',
        'tags' => 'array',
        'track_inventory' => 'boolean',
        'materials_required' => 'array',
        'cost_of_goods' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // New relationships
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function personalizationOptions()
    {
        return $this->hasMany(ProductPersonalizationOption::class)->orderBy('order');
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'product_materials')
            ->withPivot('quantity_required')
            ->withTimestamps();
    }

    public function seo()
    {
        return $this->hasOne(ProductSeo::class);
    }

    public function flashSales()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_products')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->reviews()->where('status', 'approved');
    }

    public function wishlistItems()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Helper methods
    public function isMadeToOrder(): bool
    {
        return in_array($this->inventory_type, ['made_to_order', 'both']);
    }

    public function isInStock(): bool
    {
        return in_array($this->inventory_type, ['in_stock', 'both']);
    }

    public function getAvailableQuantity(): int
    {
        if (!$this->track_inventory) {
            return 999999; // Unlimited
        }

        return max(0, $this->quantity);
    }

    public function decrementInventory(int $quantity, ?int $orderId = null): void
    {
        if (!$this->track_inventory) {
            return;
        }

        $previousQuantity = $this->quantity;
        $this->quantity = max(0, $this->quantity - $quantity);
        $this->save();

        // Create transaction record
        InventoryTransaction::create([
            'product_id' => $this->id,
            'type' => 'sale',
            'quantity' => -$quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $this->quantity,
            'order_id' => $orderId,
        ]);
    }

    public function incrementInventory(int $quantity, ?string $notes = null): void
    {
        if (!$this->track_inventory) {
            return;
        }

        $previousQuantity = $this->quantity;
        $this->quantity += $quantity;
        $this->save();

        // Create transaction record
        InventoryTransaction::create([
            'product_id' => $this->id,
            'type' => 'purchase',
            'quantity' => $quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $this->quantity,
            'notes' => $notes,
        ]);
    }

    /**
     * Get the effective SEO title, falling back to meta_title or name.
     */
    public function getEffectiveSeoTitle(): string
    {
        if ($this->seo && $this->seo->seo_title) {
            return $this->seo->seo_title;
        }

        if ($this->meta_title) {
            return $this->meta_title;
        }

        return (string) $this->name;
    }

    /**
     * Get the effective SEO description, falling back to meta_description or description.
     */
    public function getEffectiveSeoDescription(): ?string
    {
        if ($this->seo && $this->seo->seo_description) {
            return $this->seo->seo_description;
        }

        if ($this->meta_description) {
            return $this->meta_description;
        }

        if ($this->description) {
            $desc = strip_tags($this->description);
            return mb_substr($desc, 0, 180);
        }

        return null;
    }

    /**
     * Get basic keyword suggestions from name, category and tags when explicit keywords are missing.
     */
    public function getEffectiveSeoKeywords(): ?string
    {
        if ($this->seo && $this->seo->seo_keywords) {
            return $this->seo->seo_keywords;
        }

        $keywords = [];

        if ($this->name) {
            $keywords[] = $this->name;
        }

        if ($this->category && $this->category->name) {
            $keywords[] = $this->category->name;
        }

        if (is_array($this->tags)) {
            foreach ($this->tags as $tag) {
                if (is_string($tag)) {
                    $keywords[] = $tag;
                } elseif (is_array($tag) && isset($tag['name'])) {
                    $keywords[] = $tag['name'];
                }
            }
        }

        if (empty($keywords)) {
            return null;
        }

        return implode(', ', array_values(array_unique($keywords)));
    }
}
