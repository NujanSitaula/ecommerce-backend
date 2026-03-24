<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class CheckoutPaymentController extends Controller
{
    public function charge(Request $request)
    {
        $data = $request->validate([
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'amount' => 'required|integer|min:1',
            'currency' => 'sometimes|string|size:3',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $method = PaymentMethod::where('id', $data['payment_method_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            $intent = $stripe->paymentIntents->create([
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'usd',
                'customer' => $method->stripe_customer_id,
                'payment_method' => $method->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
            ]);

            return response()->json([
                'status' => $intent->status,
                'id' => $intent->id,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}


