<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSeo;

class ProductSeoEvaluator
{
    public function evaluate(Product $product, ?ProductSeo $seo = null): ProductSeo
    {
        $seo = $seo ?? new ProductSeo(['product_id' => $product->id]);

        $title = $seo->seo_title ?: $product->meta_title ?: $product->name;
        $description = $seo->seo_description ?: $product->meta_description ?: ($product->description ?? '');
        $keywords = $seo->seo_keywords;
        $ogTitle = $seo->og_title ?: $title;
        $ogDescription = $seo->og_description ?: $description;
        $ogImage = $seo->og_image_url ?: $product->thumbnail_url ?: $product->original_url;

        $score = 0;
        $maxScore = 8;

        // Title length 30-65 chars
        $titleLen = mb_strlen((string) $title);
        if ($title && $titleLen >= 30 && $titleLen <= 65) {
            $score += 2;
        } elseif ($title) {
            $score += 1;
        }

        // Description length 70-170 chars
        $descLen = mb_strlen((string) $description);
        if ($description && $descLen >= 70 && $descLen <= 170) {
            $score += 2;
        } elseif ($description) {
            $score += 1;
        }

        // Keywords present
        if ($keywords && trim((string) $keywords) !== '') {
            $score += 1;
        }

        // OG basics
        if ($ogTitle && $ogDescription && $ogImage) {
            $score += 3;
        } elseif ($ogTitle || $ogDescription || $ogImage) {
            $score += 1;
        }

        // Map score to status
        $ratio = $maxScore > 0 ? $score / $maxScore : 0;
        if ($ratio >= 0.75) {
            $status = 'green';
        } elseif ($ratio >= 0.4) {
            $status = 'yellow';
        } else {
            $status = 'red';
        }

        $seo->seo_status = $status;
        $seo->seo_score = (int) round($ratio * 100);

        return $seo;
    }
}

