<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoicePdfService $invoicePdfService
    ) {
    }

    /**
     * Download invoice for an order (authenticated customer)
     */
    public function download(string $id)
    {
        $order = Order::findOrFail($id);

        if ($order->isCancelled()) {
            return response()->json(['message' => 'Invoice not available for cancelled orders.'], 404);
        }

        $user = Auth::guard('api')->user();
        if (!$user || (int) $order->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $this->respondPdf($order);
    }

    /**
     * Download invoice for guest order (email verification)
     */
    public function downloadGuest(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        if ($order->isCancelled()) {
            return response()->json(['message' => 'Invoice not available for cancelled orders.'], 404);
        }

        $email = $request->query('email');
        if (!$email || !$order->guest_email || strcasecmp(trim($email), trim($order->guest_email)) !== 0) {
            return response()->json(['message' => 'Unauthorized. Provide email to access guest order invoice.'], 403);
        }

        return $this->respondPdf($order);
    }

    private function respondPdf(Order $order)
    {
        $pdf = $this->invoicePdfService->generate($order);
        $filename = 'invoice-' . $order->id . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
