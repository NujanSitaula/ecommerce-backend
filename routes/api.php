<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ContactNumberController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\CheckoutPaymentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\AdminProductController;
use App\Http\Controllers\Api\AdminProductSeoController;
use App\Http\Controllers\Api\AdminSettingsController;
use App\Http\Controllers\Api\AdminTaxRateController;
use App\Http\Controllers\Api\AdminProductImageController;
use App\Http\Controllers\Api\AdminMediaController;
use App\Http\Controllers\Api\AdminInventoryController;
use App\Http\Controllers\Api\AdminPersonalizationController;
use App\Http\Controllers\Api\AdminMaterialsController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AdminRefundController;
use App\Http\Controllers\Api\AdminTransactionController;
use App\Http\Controllers\Api\SearchHistoryController;
use App\Http\Controllers\Api\FlashSaleController;
use App\Http\Controllers\Api\AdminFlashSaleController;
use App\Http\Controllers\Api\AdminCouponController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminTodoController;
use App\Http\Controllers\Api\AdminNoteController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AdminCountryController;
use App\Http\Controllers\Api\AdminStateController;
use App\Http\Controllers\Api\AdminCategoryController;
use App\Http\Controllers\Api\AdminPostController;
use App\Http\Controllers\Api\AdminTagController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AdminReviewController;
use App\Http\Controllers\Api\WishlistController;

Route::get('/test', function (Request $request) {
    return response()->json([
        'message' => 'Laravel API is working!',
        'version' => app()->version(),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // Aliases for legacy/client compatibility
    Route::post('/forgot-password.json', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password.json', [AuthController::class, 'resetPassword']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::get('/me', [AuthController::class, 'profile']); // Alias for admin panel compatibility
    });
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/popular', [ProductController::class, 'popular']);
Route::get('/products/best-seller', [ProductController::class, 'bestSeller']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/flash-sale', [FlashSaleController::class, 'show']);

// Public product reviews (approved only)
Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);

// Same-category recommendations
Route::get('/products/{slug}/related', [ProductController::class, 'related']);

// Guest checkout - orders can be created without authentication
// Controller will manually check for authentication token
Route::post('/orders', [OrderController::class, 'store']);

// Guest invoice download (no auth, email required)
Route::get('/orders/{id}/invoice/guest', [InvoiceController::class, 'downloadGuest']);
// Guest-accessible shipping quote (by country_id); authenticated users can still quote by address_id
Route::get('/shipping/quote', [ShippingController::class, 'quote']);

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/phone', [ProfileController::class, 'updatePhone']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/email/verify-otp', [ProfileController::class, 'verifyEmailChangeOtp']);
    Route::post('/profile/email/resend-otp', [ProfileController::class, 'resendEmailChangeOtp']);
    Route::post('/profile/email/cancel', [ProfileController::class, 'cancelEmailChange']);
    Route::get('/contact-numbers', [ContactNumberController::class, 'index']);
    Route::post('/contact-numbers', [ContactNumberController::class, 'store']);
    Route::put('/contact-numbers/{contactNumber}', [ContactNumberController::class, 'update']);
    Route::delete('/contact-numbers/{contactNumber}', [ContactNumberController::class, 'destroy']);
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods/setup-intent', [PaymentMethodController::class, 'createSetupIntent']);
    Route::post('/payment-methods/confirm', [PaymentMethodController::class, 'confirm']);
    Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
    Route::post('/payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault']);
    Route::post('/checkout/charge', [CheckoutPaymentController::class, 'charge']);
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/orders/{id}/invoice', [InvoiceController::class, 'download']);
    Route::post('/coupons/apply', [CouponController::class, 'apply']);

    // Customer review submission (pending admin approval)
    Route::post('/products/{slug}/reviews', [ReviewController::class, 'store']);

    // Search history (mobile app + other clients)
    Route::get('/search/history', [SearchHistoryController::class, 'index']);
    Route::post('/search/history', [SearchHistoryController::class, 'store']);
    Route::delete('/search/history/{id}', [SearchHistoryController::class, 'destroy']);
    Route::delete('/search/history', [SearchHistoryController::class, 'clear']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::get('/wishlist/ids', [WishlistController::class, 'ids']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);
});

// Admin routes - require authentication and admin role
Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Products management
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::get('/products/{id}', [AdminProductController::class, 'show']);
    Route::put('/products/{id}', [AdminProductController::class, 'update']);
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);

    // Product SEO management
    Route::get('/products/{id}/seo', [AdminProductSeoController::class, 'show']);
    Route::put('/products/{id}/seo', [AdminProductSeoController::class, 'update']);

    // Product images
    Route::post('/products/images', [AdminProductImageController::class, 'upload']);
    Route::delete('/products/images/{imagePath}', [AdminProductImageController::class, 'delete']);

    // Settings management
    Route::get('/settings', [AdminSettingsController::class, 'index']);
    Route::put('/settings', [AdminSettingsController::class, 'update']);

    // Tax rates management
    Route::get('/tax-rates', [AdminTaxRateController::class, 'index']);
    Route::post('/tax-rates', [AdminTaxRateController::class, 'store']);
    Route::get('/tax-rates/{id}', [AdminTaxRateController::class, 'show']);
    Route::put('/tax-rates/{id}', [AdminTaxRateController::class, 'update']);
    Route::delete('/tax-rates/{id}', [AdminTaxRateController::class, 'destroy']);

    // Country management
    Route::get('/countries', [AdminCountryController::class, 'index']);
    Route::post('/countries', [AdminCountryController::class, 'store']);
    Route::put('/countries/{country}', [AdminCountryController::class, 'update']);
    Route::delete('/countries/{country}', [AdminCountryController::class, 'destroy']);

    // State management (scoped to country)
    Route::get('/countries/{country}/states', [AdminStateController::class, 'index']);
    Route::post('/countries/{country}/states', [AdminStateController::class, 'store']);
    Route::put('/countries/{country}/states/{state}', [AdminStateController::class, 'update']);
    Route::delete('/countries/{country}/states/{state}', [AdminStateController::class, 'destroy']);

    // Media library
    Route::get('/media', [AdminMediaController::class, 'index']);
    Route::post('/media', [AdminMediaController::class, 'store']);
    Route::get('/media/{id}', [AdminMediaController::class, 'show']);
    Route::put('/media/{id}', [AdminMediaController::class, 'update']);
    Route::delete('/media/{id}', [AdminMediaController::class, 'destroy']);
    Route::post('/media/{id}/edit', [AdminMediaController::class, 'saveEditedVersion']);

    // Inventory management
    Route::get('/inventory', [AdminInventoryController::class, 'index']);
    Route::get('/inventory/low-stock', [AdminInventoryController::class, 'lowStock']);
    Route::get('/inventory/transactions', [AdminInventoryController::class, 'transactions']);
    Route::get('/inventory/{productId}', [AdminInventoryController::class, 'show']);
    Route::post('/inventory/{productId}/adjust', [AdminInventoryController::class, 'adjust']);

    // Personalization options
    Route::get('/products/{productId}/personalizations', [AdminPersonalizationController::class, 'index']);
    Route::post('/products/{productId}/personalizations', [AdminPersonalizationController::class, 'store']);
    Route::put('/personalizations/{id}', [AdminPersonalizationController::class, 'update']);
    Route::delete('/personalizations/{id}', [AdminPersonalizationController::class, 'destroy']);

    // Materials management
    Route::get('/materials', [AdminMaterialsController::class, 'index']);
    Route::post('/materials', [AdminMaterialsController::class, 'store']);
    Route::get('/materials/low-stock', [AdminMaterialsController::class, 'lowStock']);
    Route::get('/materials/{id}', [AdminMaterialsController::class, 'show']);
    Route::put('/materials/{id}', [AdminMaterialsController::class, 'update']);
    Route::delete('/materials/{id}', [AdminMaterialsController::class, 'destroy']);
    Route::post('/materials/{id}/adjust-stock', [AdminMaterialsController::class, 'adjustStock']);

    // Order management
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::get('/orders/{id}/invoice', [AdminOrderController::class, 'downloadInvoice']);
    Route::put('/orders/{id}', [AdminOrderController::class, 'update']);
    Route::put('/orders/{id}/cancel', [AdminOrderController::class, 'cancel']);
    Route::post('/orders/{id}/items', [AdminOrderController::class, 'addItem']);
    Route::delete('/orders/{id}/items/{itemId}', [AdminOrderController::class, 'removeItem']);
    Route::get('/orders/{id}/modifications', [AdminOrderController::class, 'modifications']);
    Route::get('/orders/{id}/transactions', [AdminOrderController::class, 'transactions']);
    Route::put('/orders/{id}/items/{itemId}/production-status', [AdminOrderController::class, 'updateProductionStatus']);
    Route::post('/orders/{id}/items/{itemId}/start-production', [AdminOrderController::class, 'startProduction']);
    Route::post('/orders/{id}/items/{itemId}/complete-production', [AdminOrderController::class, 'completeProduction']);

    // Refund management
    Route::get('/refunds/pending', [AdminRefundController::class, 'pending']);
    Route::post('/orders/{orderId}/refunds/{transactionId}/approve', [AdminRefundController::class, 'approve']);
    Route::post('/orders/{orderId}/refunds/{transactionId}/reject', [AdminRefundController::class, 'reject']);
    Route::post('/orders/{orderId}/refunds/{transactionId}/process', [AdminRefundController::class, 'process']);

    // Transactions
    Route::get('/transactions/reconciliation', [AdminTransactionController::class, 'reconciliation']);
    Route::get('/transactions/{id}', [AdminTransactionController::class, 'show']);
    Route::get('/transactions', [AdminTransactionController::class, 'index']);

    // Flash Sale management
    Route::get('/flash-sale', [AdminFlashSaleController::class, 'getCurrent']);
    Route::put('/flash-sale', [AdminFlashSaleController::class, 'upsert']);

    // User management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);

    // Admin todos (personal productivity)
    Route::get('/todos', [AdminTodoController::class, 'index']);
    Route::post('/todos', [AdminTodoController::class, 'store']);
    Route::put('/todos/{id}/toggle', [AdminTodoController::class, 'toggle']);
    Route::delete('/todos/{id}', [AdminTodoController::class, 'destroy']);

    // Admin quick note
    Route::get('/quick-note', [AdminNoteController::class, 'show']);
    Route::put('/quick-note', [AdminNoteController::class, 'update']);

    // Coupon management
    Route::get('/coupons', [AdminCouponController::class, 'index']);
    Route::post('/coupons', [AdminCouponController::class, 'store']);
    Route::get('/coupons/{id}', [AdminCouponController::class, 'show']);
    Route::put('/coupons/{id}', [AdminCouponController::class, 'update']);
    Route::delete('/coupons/{id}', [AdminCouponController::class, 'destroy']);

    // Category management
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

    // Blog posts management
    Route::get('/posts', [AdminPostController::class, 'index']);
    Route::post('/posts', [AdminPostController::class, 'store']);
    Route::get('/posts/{post}', [AdminPostController::class, 'show']);
    Route::put('/posts/{post}', [AdminPostController::class, 'update']);
    Route::delete('/posts/{post}', [AdminPostController::class, 'destroy']);

    // Tags (for blog)
    Route::get('/tags', [AdminTagController::class, 'index']);

    // Reviews moderation
    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::post('/reviews/{id}/approve', [AdminReviewController::class, 'approve']);
    Route::post('/reviews/{id}/reject', [AdminReviewController::class, 'reject']);
    Route::post('/reviews/{id}/hide', [AdminReviewController::class, 'hide']);
    Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);
});

