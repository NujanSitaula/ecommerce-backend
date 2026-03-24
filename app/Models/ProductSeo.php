<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSeo extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image_url',
        'og_type',
        'og_url_override',
        'twitter_title',
        'twitter_description',
        'twitter_image_url',
        'twitter_card_type',
        'seo_status',
        'seo_score',
    ];

    protected $casts = [
        'seo_score' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

