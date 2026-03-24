<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    /**
     * Get store settings
     */
    public function index()
    {
        // In a real app, you'd store this in a database table
        // For now, we'll use cache/config as a simple storage
        $settings = [
            'name' => config('app.name', 'Herd Store'),
            'supportEmail' => config('mail.from.address', 'support@example.com'),
            'currency' => 'USD',
            'paymentProvider' => 'stripe',
            'shippingFrom' => null,
            'taxRate' => 0,
            'notes' => null,
            'invoiceLogo' => null,
            'invoiceCompanyName' => null,
            'invoiceAddress' => null,
            'invoiceEmail' => null,
            'invoicePhone' => null,
            'additional' => [],
        ];

        // Try to get from cache first
        $cached = Cache::get('store_settings');
        if ($cached) {
            $settings = array_merge($settings, $cached);
        }

        return response()->json($settings);
    }

    /**
     * Update store settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'supportEmail' => ['nullable', 'email'],
            'currency' => ['nullable', 'string', 'size:3'],
            'paymentProvider' => ['nullable', 'string'],
            'shippingFrom' => ['nullable', 'string'],
            'taxRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'invoiceLogo' => ['nullable', 'string', 'max:2000'],
            'invoiceCompanyName' => ['nullable', 'string', 'max:255'],
            'invoiceAddress' => ['nullable', 'string', 'max:1000'],
            'invoiceEmail' => ['nullable', 'email'],
            'invoicePhone' => ['nullable', 'string', 'max:50'],
            'additional' => ['nullable', 'array'],
        ]);

        // Store in cache (in production, use a database table)
        $current = Cache::get('store_settings', []);
        $updated = array_merge($current, $validated);
        Cache::put('store_settings', $updated, now()->addYears(1));

        return response()->json($updated);
    }
}
