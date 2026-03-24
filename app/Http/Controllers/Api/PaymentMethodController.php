<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PaymentMethodController extends Controller
{
    public function __construct(protected StripeService $stripeService)
    {
    }

    public function index()
    {
        $methods = PaymentMethod::where('user_id', Auth::id())
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return PaymentMethodResource::collection($methods);
    }

    public function createSetupIntent()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $clientSecret = $this->stripeService->createSetupIntent($user);
            return response()->json(['client_secret' => $clientSecret]);
        } catch (ApiErrorException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'payment_method_id' => 'required|string',
            'make_default' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            $customerId = $this->stripeService->ensureCustomer($user);

            // Attach payment method to customer
            $stripe->paymentMethods->attach(
                $data['payment_method_id'],
                ['customer' => $customerId]
            );

            // Retrieve payment method details
            $pm = $stripe->paymentMethods->retrieve($data['payment_method_id']);
            $card = $pm->card ?? null;
            $cardholderName = $pm->billing_details->name ?? null;

            if (!empty($data['make_default'])) {
                PaymentMethod::where('user_id', $user->id)->update(['is_default' => false]);
            }

            $method = PaymentMethod::create([
                'user_id' => $user->id,
                'provider' => 'stripe',
                'stripe_customer_id' => $customerId,
                'stripe_payment_method_id' => $pm->id,
                'brand' => $card?->brand,
                'last4' => $card?->last4,
                'cardholder_name' => $cardholderName,
                'exp_month' => $card?->exp_month,
                'exp_year' => $card?->exp_year,
                'is_default' => !empty($data['make_default']),
            ]);

            return new PaymentMethodResource($method);
        } catch (ApiErrorException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $user = Auth::user();
        if (!$user || $paymentMethod->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            if ($paymentMethod->stripe_payment_method_id) {
                $stripe->paymentMethods->detach($paymentMethod->stripe_payment_method_id);
            }
        } catch (ApiErrorException $e) {
            // Ignore Stripe errors on detach, still remove locally
        }

        $paymentMethod->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function setDefault(PaymentMethod $paymentMethod)
    {
        $user = Auth::user();
        if (!$user || $paymentMethod->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        PaymentMethod::where('user_id', $user->id)->update(['is_default' => false]);
        $paymentMethod->is_default = true;
        $paymentMethod->save();

        return new PaymentMethodResource($paymentMethod);
    }
}


