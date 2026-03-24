<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;

class AdminTaxRateController extends Controller
{
    /**
     * List all tax rates
     */
    public function index()
    {
        $taxRates = TaxRate::with(['country', 'state'])
            ->orderBy('is_default', 'desc')
            ->orderBy('country_id')
            ->orderBy('state_id')
            ->get();

        return response()->json($taxRates);
    }

    /**
     * Create a new tax rate
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'name' => ['required', 'string', 'max:255'],
            'tax_type' => ['required', 'string', 'in:vat,sales_tax,gst,hst,pst,qst'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'shipping_taxable' => ['boolean'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $validated['shipping_taxable'] = $validated['shipping_taxable'] ?? true;
        $validated['is_default'] = $validated['is_default'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // If setting as default, unset other defaults
        if ($validated['is_default']) {
            TaxRate::where('is_default', true)->update(['is_default' => false]);
            $validated['country_id'] = null;
            $validated['state_id'] = null;
        }

        $taxRate = TaxRate::create($validated);

        return response()->json($taxRate->load(['country', 'state']), 201);
    }

    /**
     * Get a single tax rate
     */
    public function show(string $id)
    {
        $taxRate = TaxRate::with(['country', 'state'])->findOrFail($id);

        return response()->json($taxRate);
    }

    /**
     * Update a tax rate
     */
    public function update(Request $request, string $id)
    {
        $taxRate = TaxRate::findOrFail($id);

        $validated = $request->validate([
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'tax_type' => ['sometimes', 'string', 'in:vat,sales_tax,gst,hst,pst,qst'],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'shipping_taxable' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['is_default']) && $validated['is_default']) {
            TaxRate::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
            $validated['country_id'] = null;
            $validated['state_id'] = null;
        }

        $taxRate->update($validated);

        return response()->json($taxRate->fresh(['country', 'state']));
    }

    /**
     * Delete a tax rate
     */
    public function destroy(string $id)
    {
        $taxRate = TaxRate::findOrFail($id);

        if ($taxRate->is_default) {
            return response()->json([
                'message' => 'Cannot delete the default tax rate.',
            ], 422);
        }

        $taxRate->delete();

        return response()->json(null, 204);
    }
}
