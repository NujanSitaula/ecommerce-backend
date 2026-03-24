<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderSuccessMail;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\ContactNumber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPersonalization;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Coupon;
use App\Services\CouponService;
use App\Services\InventoryService;
use App\Services\ShippingService;
use App\Services\TaxService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class OrderController extends Controller
{
    public function __construct(
        protected CouponService $couponService,
        protected ShippingService $shippingService,
        protected TaxService $taxService,
        protected InventoryService $inventoryService,
        protected TransactionService $transactionService,
    ) {
    }
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->with(['items.product', 'address', 'contactNumber', 'paymentMethod'])
            ->orderByDesc('created_at')
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        // Manually authenticate user from bearer token if present
        // Since route is not protected by middleware, we need to manually validate Passport token
        $user = null;
        $token = $request->bearerToken();
        
        if ($token) {
            try {
                // Use Passport's TokenGuard to validate the token
                // We need to manually set up the guard since middleware isn't applied
                $guard = Auth::guard('api');
                
                // Create a PSR-7 request with the bearer token
                $psrRequest = \GuzzleHttp\Psr7\ServerRequest::fromGlobals()
                    ->withHeader('Authorization', 'Bearer ' . $token);
                
                // Use Passport's resource server to validate
                $resourceServer = app(\League\OAuth2\Server\ResourceServer::class);
                $validatedRequest = $resourceServer->validateAuthenticatedRequest($psrRequest);
                
                // Get user ID from validated request
                $userId = $validatedRequest->getAttribute('oauth_user_id');
                if ($userId) {
                    $user = \App\Models\User::find($userId);
                }
            } catch (\Exception $e) {
                // Token might be invalid, expired, or revoked - treat as guest
                \Log::warning('Token validation failed: ' . $e->getMessage());
                $user = null;
            }
        }
        
        $isGuest = !$user;
        
        // Debug: Log authentication status
        \Log::info('Order checkout - Token present: ' . ($token ? 'Yes' : 'No') . ', User authenticated: ' . ($user ? 'Yes (ID: ' . $user->id . ')' : 'No (Guest)'));

        // Validation rules - different for authenticated vs guest
        $rules = [
            'delivery_date' => 'required|date',
            'gift_wrapped' => 'required|boolean',
            'delivery_instructions' => 'nullable|string|max:1000',
            'leave_at_door' => 'nullable|boolean',
            'coupon_code' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.name' => 'required|string',
            'items.*.slug' => 'required|string',
        ];

        if ($isGuest) {
            // Guest checkout validation - explicitly require guest fields
            $rules = array_merge($rules, [
                'guest_email' => 'required|email|max:255',
                'guest_address' => 'required|array',
                'guest_address.first_name' => 'required|string|max:255',
                'guest_address.last_name' => 'required|string|max:255',
                'guest_address.address_line1' => 'required|string|max:255',
                'guest_address.address_line2' => 'nullable|string|max:255',
                'guest_address.city' => 'required|string|max:255',
                'guest_address.postal_code' => 'required|string|max:20',
                'guest_address.country_id' => 'required|integer|exists:countries,id',
                'guest_address.state_id' => 'nullable|integer|exists:states,id',
                'guest_contact' => 'required|array',
                'guest_contact.phone' => 'required|string|max:20',
                'guest_payment_method_id' => 'required|string', // Stripe payment method ID
                // Explicitly exclude authenticated user fields for guests
                'address_id' => 'prohibited',
                'contact_number_id' => 'prohibited',
                'payment_method_id' => 'prohibited',
            ]);
        } else {
            // Authenticated checkout validation - explicitly require user fields
            $rules = array_merge($rules, [
                'address_id' => 'required|integer|exists:addresses,id',
                'contact_number_id' => 'required|integer|exists:contact_numbers,id',
                'payment_method_id' => 'required|integer|exists:payment_methods,id',
                // Explicitly exclude guest fields for authenticated users
                'guest_email' => 'prohibited',
                'guest_address' => 'prohibited',
                'guest_contact' => 'prohibited',
                'guest_payment_method_id' => 'prohibited',
            ]);
        }

        $data = $request->validate($rules);

        // Double-check: if user is authenticated but guest fields are present, reject
        if (!$isGuest && ($request->has('guest_email') || $request->has('guest_address') || $request->has('guest_contact'))) {
            return response()->json([
                'message' => 'Guest fields are not allowed for authenticated users. Please use address_id, contact_number_id, and payment_method_id instead.',
            ], 422);
        }

        // Double-check: if user is guest but authenticated user fields are present, reject
        if ($isGuest && ($request->has('address_id') || $request->has('contact_number_id') || $request->has('payment_method_id'))) {
            return response()->json([
                'message' => 'Authenticated user fields are not allowed for guest checkout. Please use guest_email, guest_address, guest_contact, and guest_payment_method_id instead.',
            ], 422);
        }

        // Get address, contact, and payment method based on checkout type
        if ($isGuest) {
            // For guests, create temporary Address object for shipping calculation
            $guestAddressData = $data['guest_address'];
            $address = new Address();
            $address->address_line1 = $guestAddressData['address_line1'];
            $address->address_line2 = $guestAddressData['address_line2'] ?? null;
            $address->city = $guestAddressData['city'];
            $address->postal_code = $guestAddressData['postal_code'];
            $address->name = ($guestAddressData['first_name'] ?? '') . ' ' . ($guestAddressData['last_name'] ?? '');
            $address->country_id = $guestAddressData['country_id'];
            $address->state_id = $guestAddressData['state_id'] ?? null;
            // Load relationships for shipping calculation
            $address->load('country', 'state');

            $guestContact = $data['guest_contact'];
            $guestPaymentMethodId = $data['guest_payment_method_id'];
            $guestEmail = $data['guest_email'];
            $guestName = trim(($guestAddressData['first_name'] ?? '') . ' ' . ($guestAddressData['last_name'] ?? ''));

            $contactNumber = null;
            $paymentMethod = null;
        } else {
            // Verify ownership of address, contact number, and payment method
            $address = Address::with(['country', 'state'])
                ->where('id', $data['address_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $contactNumber = ContactNumber::where('id', $data['contact_number_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $paymentMethod = PaymentMethod::where('id', $data['payment_method_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
        }

        // Calculate base totals
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Base shipping fee from rules
        $shippingFee = $this->shippingService->calculateShippingFee($address);

        // Automatic free shipping threshold (e.g., subtotal >= 100)
        $FREE_SHIPPING_THRESHOLD = 100;
        $shippingDiscount = 0.0;
        if ($subtotal >= $FREE_SHIPPING_THRESHOLD) {
            $shippingDiscount = $shippingFee;
        }

        $discountAmount = 0.0;
        $appliedCoupon = null;

        // Apply coupon if provided (only for authenticated users for now)
        if (!empty($data['coupon_code'])) {
            if ($isGuest) {
                // For guests, we can still validate and apply coupons, but skip user-specific checks
                // This would require updating CouponService to handle guest users
                // For now, we'll skip coupon validation for guests
                return response()->json([
                    'message' => 'Coupons are not available for guest checkout.',
                ], 422);
            }

            try {
                $appliedCoupon = $this->couponService->findAndValidate(
                    $data['coupon_code'],
                    $user,
                    $subtotal
                );

                $discounts = $this->couponService->calculateDiscount(
                    $appliedCoupon,
                    $subtotal,
                    $shippingFee
                );

                $discountAmount = $discounts['discount_amount'];
                // Coupon shipping discount overrides threshold-based discount
                $shippingDiscount = $discounts['shipping_discount'];
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'message' => 'Invalid coupon.',
                    'errors' => $e->errors(),
                ], 422);
            }
        }

        // Final shipping fee
        $finalShippingFee = max(0.0, $shippingFee - $shippingDiscount);

        // Tax calculation
        $taxInfo = $this->taxService->getTaxForAddress($address);
        $taxResult = $this->taxService->calculateTax(
            $subtotal,
            $discountAmount,
            $finalShippingFee,
            $taxInfo
        );
        $taxAmount = $taxResult['tax_amount'];

        // Total = subtotal - discount + shipping + tax
        $total = max(0.0, $subtotal - $discountAmount + $finalShippingFee + $taxAmount);

        // Charge payment method
        $stripe = new StripeClient(config('services.stripe.secret'));

        // Build billing details from address
        $billingAddress = [];
        if ($address->address_line1) {
            $billingAddress['line1'] = $address->address_line1;
        }
        if ($address->address_line2) {
            $billingAddress['line2'] = $address->address_line2;
        }
        if ($address->city) {
            $billingAddress['city'] = $address->city;
        }
        if ($address->postal_code) {
            $billingAddress['postal_code'] = $address->postal_code;
        }
        if ($address->state) {
            $billingAddress['state'] = $address->state->name ?? $address->state->code;
        }
        if ($address->country && $address->country->iso2) {
            $billingAddress['country'] = strtoupper($address->country->iso2);
        }

        $billingDetails = [];
        if ($isGuest) {
            $billingDetails['name'] = $guestName;
            $billingDetails['email'] = $guestEmail;
            $billingDetails['phone'] = $guestContact['phone'];
        } else {
            if ($address->name || $user->name) {
                $billingDetails['name'] = $address->name ?? $user->name;
            }
            if ($user->email) {
                $billingDetails['email'] = $user->email;
            }
            if ($contactNumber->phone) {
                $billingDetails['phone'] = $contactNumber->phone;
            }
        }
        if (!empty($billingAddress)) {
            $billingDetails['address'] = $billingAddress;
        }

        try {
            // Update payment method with billing details first (only for authenticated users)
            if (!$isGuest && !empty($billingDetails)) {
                try {
                    $stripe->paymentMethods->update(
                        $paymentMethod->stripe_payment_method_id,
                        ['billing_details' => $billingDetails]
                    );
                } catch (ApiErrorException $e) {
                    // Log error but continue - billing details update is not critical
                    \Log::warning('Failed to update payment method billing details: ' . $e->getMessage());
                }
            }

            // Build metadata for Stripe
            $metadata = [
                'order_subtotal' => (string) $subtotal,
                'shipping_fee_base' => (string) $shippingFee,
                'order_shipping_fee' => (string) $finalShippingFee,
                'order_total' => (string) $total,
            ];

            if ($appliedCoupon) {
                $metadata['coupon_code'] = $appliedCoupon->code;
                $metadata['coupon_type'] = $appliedCoupon->type;
            }

            if ($discountAmount > 0) {
                $metadata['discount_amount'] = (string) $discountAmount;
            }

            if ($shippingDiscount > 0) {
                $metadata['shipping_discount'] = (string) $shippingDiscount;
            }

            if ($taxAmount > 0) {
                $metadata['tax_amount'] = (string) $taxAmount;
                $metadata['tax_rate'] = (string) ($taxInfo['rate'] ?? 0);
                $metadata['tax_type'] = $taxInfo['tax_type'] ?? 'vat';
            }

            // Create payment intent
            $intentParams = [
                'amount' => (int)($total * 100), // Convert to cents
                'currency' => 'usd',
                'payment_method' => $isGuest ? $guestPaymentMethodId : $paymentMethod->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => $metadata,
            ];

            if ($isGuest) {
                // For guests, create payment intent without customer (one-time payment)
                $intentParams['billing_details'] = $billingDetails;
            } else {
                // For authenticated users, use existing customer
                $intentParams['customer'] = $paymentMethod->stripe_customer_id;
            }

            $intent = $stripe->paymentIntents->create($intentParams);

            // Create order in database
            DB::beginTransaction();

            try {
                $orderData = [
                    'user_id' => $isGuest ? null : $user->id,
                    'guest_email' => $isGuest ? $guestEmail : null,
                    'guest_name' => $isGuest ? $guestName : null,
                    'guest_address' => $isGuest ? [
                        'name' => $address->name,
                        'address_line1' => $address->address_line1,
                        'address_line2' => $address->address_line2,
                        'city' => $address->city,
                        'postal_code' => $address->postal_code,
                        'country_id' => $address->country_id,
                        'state_id' => $address->state_id,
                    ] : null,
                    'address_id' => $isGuest ? null : $address->id,
                    'contact_number_id' => $isGuest ? null : $contactNumber->id,
                    'payment_method_id' => $isGuest ? null : $paymentMethod->id,
                    'coupon_id' => $appliedCoupon?->id,
                    'stripe_payment_intent_id' => $intent->id,
                    'delivery_date' => $data['delivery_date'],
                    'gift_wrapped' => $data['gift_wrapped'],
                    'delivery_instructions' => $data['delivery_instructions'] ?? null,
                    'leave_at_door' => $data['leave_at_door'] ?? false,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $finalShippingFee,
                    'discount_amount' => $discountAmount,
                    'shipping_discount' => $shippingDiscount,
                    'tax_amount' => $taxAmount,
                    'tax_rate' => $taxInfo['rate'] ?? 0,
                    'tax_type' => $taxInfo['tax_type'] ?? 'vat',
                    'total' => $total,
                    'status' => 'pending',
                ];

                $order = Order::create($orderData);

                // Create order items and handle inventory
                foreach ($data['items'] as $itemData) {
                    $product = null;
                    $variantId = $itemData['variant_id'] ?? null;
                    
                    // Try to find product by ID first
                    if (is_numeric($itemData['id'])) {
                        $product = Product::find($itemData['id']);
                        
                        // If product not found, the ID might be a variant ID
                        if (!$product) {
                            $variant = \App\Models\ProductVariant::find($itemData['id']);
                            if ($variant && $variant->product_id) {
                                $product = Product::find($variant->product_id);
                                // If we found product through variant, use the variant ID
                                if ($product && !$variantId) {
                                    $variantId = $variant->id;
                                }
                            }
                        }
                    }
                    
                    // If product still not found but variant_id is provided, try to find product through variant
                    if (!$product && $variantId && is_numeric($variantId)) {
                        $variant = \App\Models\ProductVariant::find($variantId);
                        if ($variant && $variant->product_id) {
                            $product = Product::find($variant->product_id);
                        }
                    }
                    
                    // If still not found, try to find by slug as fallback
                    if (!$product && !empty($itemData['slug'])) {
                        $product = Product::where('slug', $itemData['slug'])->first();
                    }

                    $isMadeToOrder = false;
                    $productionStatus = null;
                    $estimatedCompletionDate = null;

                    if ($product) {
                        // Determine if this is a made-to-order item
                        $isMadeToOrder = $product->isMadeToOrder() && 
                            ($product->inventory_type === 'made_to_order' || 
                             ($product->inventory_type === 'both' && ($itemData['is_made_to_order'] ?? false)));

                        if ($isMadeToOrder) {
                            $productionStatus = 'pending';
                            if ($product->production_time_days) {
                                $estimatedCompletionDate = Carbon::now()
                                    ->addWeekdays($product->production_time_days)
                                    ->toDateString();
                            }
                        } else {
                            // Decrement inventory for in-stock items
                            try {
                                $this->inventoryService->decrementInventory(
                                    $product,
                                    $itemData['quantity'],
                                    $variantId,
                                    $order->id
                                );
                            } catch (\Exception $e) {
                                DB::rollBack();
                                return response()->json([
                                    'message' => 'Inventory error: ' . $e->getMessage(),
                                ], 422);
                            }
                        }
                    } else {
                        // Product not found - log warning but allow order to proceed
                        \Log::warning("Product not found during order creation", [
                            'item_id' => $itemData['id'] ?? null,
                            'variant_id' => $variantId,
                            'slug' => $itemData['slug'] ?? null,
                            'name' => $itemData['name'] ?? null,
                            'order_id' => $order->id ?? null,
                        ]);
                    }

                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product?->id,
                        'variant_id' => $variantId,
                        'product_name' => $itemData['name'],
                        'product_slug' => $itemData['slug'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'subtotal' => $itemData['price'] * $itemData['quantity'],
                        'is_made_to_order' => $isMadeToOrder,
                        'production_status' => $productionStatus,
                        'estimated_completion_date' => $estimatedCompletionDate,
                    ]);
                    
                    // Log if made-to-order item was created
                    if ($isMadeToOrder) {
                        \Log::info("Made-to-order item created", [
                            'order_item_id' => $orderItem->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'production_status' => $productionStatus,
                        ]);
                    }

                    // Save personalizations if provided
                    if (isset($itemData['personalizations']) && is_array($itemData['personalizations'])) {
                        foreach ($itemData['personalizations'] as $personalizationData) {
                            OrderPersonalization::create([
                                'order_item_id' => $orderItem->id,
                                'personalization_option_id' => $personalizationData['option_id'],
                                'value' => $personalizationData['value'] ?? '',
                                'file_url' => $personalizationData['file_url'] ?? null,
                            ]);
                        }
                    }
                }

                // Record coupon redemption (only for authenticated users)
                if ($appliedCoupon && !$isGuest) {
                    $this->couponService->recordRedemption($appliedCoupon, $user, $order);
                }

                // Create payment transaction record
                $this->transactionService->createTransaction([
                    'order_id' => $order->id,
                    'type' => 'payment',
                    'status' => 'completed',
                    'amount' => $total,
                    'currency' => 'USD',
                    'stripe_payment_intent_id' => $intent->id,
                    'description' => 'Order payment - Order #' . $order->id,
                    'metadata' => [
                        'order_id' => $order->id,
                        'subtotal' => $subtotal,
                        'shipping_fee' => $finalShippingFee,
                        'discount_amount' => $discountAmount,
                        'shipping_discount' => $shippingDiscount,
                        'tax_amount' => $taxAmount,
                        'coupon_code' => $appliedCoupon?->code,
                    ],
                    'created_by' => $user?->id ?? null,
                    'processed_at' => now(),
                ]);

                DB::commit();

                // Load relationships (only if they exist)
                if (!$isGuest) {
                    $order->load(['items.product', 'address', 'contactNumber', 'paymentMethod']);
                } else {
                    $order->load(['items.product']);
                }

                // Queue order confirmation email after the transaction commits.
                $recipientEmail = $order->guest_email ?? ($user?->email ?? null);
                $customerName = $order->guest_name ?? ($user?->name ?? 'there');

                if (!empty($recipientEmail)) {
                    Mail::to($recipientEmail)->queue(
                        new OrderSuccessMail($order, $customerName),
                    );
                }

                return new OrderResource($order);
            } catch (\Exception $e) {
                DB::rollBack();
                // Payment was successful but order creation failed
                // In production, you might want to refund the payment here
                return response()->json([
                    'message' => 'Order creation failed. Payment was processed but order was not created. Please contact support.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } catch (ApiErrorException $e) {
            // Payment failed
            $errorMessage = $e->getMessage();
            if (str_contains(strtolower($errorMessage), 'declined') || 
                str_contains(strtolower($errorMessage), 'card')) {
                return response()->json([
                    'message' => 'Your card was declined. Please try a different payment method.',
                    'error' => $errorMessage,
                ], 422);
            }

            return response()->json([
                'message' => 'Payment failed. Please try again.',
                'error' => $errorMessage,
            ], 422);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['items.product', 'address', 'contactNumber', 'paymentMethod'])
            ->firstOrFail();

        return new OrderResource($order);
    }
}
