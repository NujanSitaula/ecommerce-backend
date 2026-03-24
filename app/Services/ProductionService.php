<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Material;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProductionService
{
    /**
     * Start production for an order item
     */
    public function startProduction(OrderItem $item): void
    {
        if (!$item->is_made_to_order) {
            throw new \Exception('Item is not a made-to-order item');
        }

        // Load product with materials relationship
        $item->load('product.materials');
        
        // Check and deduct materials if product has materials assigned
        if ($item->product && $item->product->materials && $item->product->materials->isNotEmpty()) {
            $this->checkMaterialAvailability($item);
            $this->deductMaterials($item);
        }

        $item->production_status = 'in_progress';
        $item->production_started_at = Carbon::now();

        // Recalculate estimated completion if production_time_days is available
        if ($item->product && $item->product->production_time_days) {
            $item->estimated_completion_date = Carbon::now()
                ->addWeekdays($item->product->production_time_days)
                ->toDateString();
        }

        $item->save();

        Log::info("Production started for order item {$item->id}");
    }

    /**
     * Complete production for an order item
     */
    public function completeProduction(OrderItem $item): void
    {
        if (!$item->is_made_to_order) {
            throw new \Exception('Item is not a made-to-order item');
        }

        $item->production_status = 'completed';
        $item->production_completed_at = Carbon::now();
        $item->save();

        Log::info("Production completed for order item {$item->id}");
    }

    /**
     * Update production status
     */
    public function updateProductionStatus(OrderItem $item, string $status): void
    {
        if (!in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
            throw new \Exception('Invalid production status');
        }

        $item->production_status = $status;

        if ($status === 'in_progress' && !$item->production_started_at) {
            // Load product with materials relationship
            $item->load('product.materials');
            
            // Check and deduct materials if product has materials assigned
            if ($item->product && $item->product->materials && $item->product->materials->isNotEmpty()) {
                $this->checkMaterialAvailability($item);
                $this->deductMaterials($item);
            }

            $item->production_started_at = Carbon::now();
            
            // Recalculate estimated completion
            if ($item->product && $item->product->production_time_days) {
                $item->estimated_completion_date = Carbon::now()
                    ->addWeekdays($item->product->production_time_days)
                    ->toDateString();
            }
        }

        if ($status === 'completed' && !$item->production_completed_at) {
            $item->production_completed_at = Carbon::now();
        }

        $item->save();

        Log::info("Production status updated to {$status} for order item {$item->id}");
    }

    /**
     * Get all pending made-to-order items
     */
    public function getPendingProductionItems()
    {
        return OrderItem::where('is_made_to_order', true)
            ->where('production_status', 'pending')
            ->with(['order.user', 'order.guest_name', 'order.guest_email', 'product.materials', 'variant', 'personalizations.personalizationOption'])
            ->orderBy('estimated_completion_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get all in-progress production items
     */
    public function getInProgressProductionItems()
    {
        return OrderItem::where('is_made_to_order', true)
            ->where('production_status', 'in_progress')
            ->with(['order.user', 'order.guest_name', 'order.guest_email', 'product.materials', 'variant', 'personalizations.personalizationOption'])
            ->orderBy('estimated_completion_date', 'asc')
            ->orderBy('production_started_at', 'asc')
            ->get();
    }

    /**
     * Calculate estimated completion date based on production time
     */
    public function calculateEstimatedCompletion(OrderItem $item): ?Carbon
    {
        if (!$item->is_made_to_order || !$item->product) {
            return null;
        }

        $productionTimeDays = $item->product->production_time_days;
        if (!$productionTimeDays) {
            return null;
        }

        $startDate = $item->production_started_at ?? Carbon::now();

        return $startDate->copy()->addWeekdays($productionTimeDays);
    }

    /**
     * Check if materials are available for production
     */
    protected function checkMaterialAvailability(OrderItem $item): void
    {
        if (!$item->product || !$item->product->materials) {
            return;
        }

        $orderQuantity = $item->quantity;
        $insufficientMaterials = [];

        foreach ($item->product->materials as $productMaterial) {
            $material = $productMaterial->material;
            if (!$material) {
                continue;
            }

            $requiredQuantity = (float) $productMaterial->pivot->quantity_required * $orderQuantity;
            $availableStock = (float) $material->current_stock;

            if ($availableStock < $requiredQuantity) {
                $insufficientMaterials[] = [
                    'material' => $material->name,
                    'required' => $requiredQuantity,
                    'available' => $availableStock,
                ];
            }
        }

        if (!empty($insufficientMaterials)) {
            $message = 'Insufficient materials: ';
            $messages = array_map(function ($item) {
                return "{$item['material']} (required: {$item['required']}, available: {$item['available']})";
            }, $insufficientMaterials);
            throw new \Exception($message . implode(', ', $messages));
        }
    }

    /**
     * Deduct materials from stock when production starts
     */
    protected function deductMaterials(OrderItem $item): void
    {
        if (!$item->product || !$item->product->materials) {
            return;
        }

        $orderQuantity = $item->quantity;

        foreach ($item->product->materials as $productMaterial) {
            $material = $productMaterial->material;
            if (!$material) {
                continue;
            }

            $requiredQuantity = (float) $productMaterial->pivot->quantity_required * $orderQuantity;
            $material->decrementStock($requiredQuantity);

            Log::info("Material deducted: {$material->name} - {$requiredQuantity} units for order item {$item->id}");
        }
    }
}
