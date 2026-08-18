<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\OrderCancellationController as AdminOrderCancellationController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\StockController as AdminStockController;
use App\Http\Controllers\Api\Admin\SummaryController as AdminSummaryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\IyzicoPaymentController;
use App\Http\Controllers\Api\OrderCancellationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\StripePaymentController as ApiStripePaymentController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/categories/{category}', [CatalogController::class, 'category']);
Route::get('/products', [CatalogController::class, 'products']);
Route::get('/products/{product}', [CatalogController::class, 'show']);
Route::get('/products/{product}/reviews', [ProductReviewController::class, 'index']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);
Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AccountController::class, 'updateProfile']);
    Route::put('/auth/password', [AccountController::class, 'updatePassword']);

    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);

    Route::get('/checkout', [CheckoutController::class, 'show']);
    Route::get('/checkout/installments', [CheckoutController::class, 'installments']);
    Route::post('/checkout', [CheckoutController::class, 'store']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice']);
    Route::get('/orders/{order}/installments', [OrderController::class, 'installments']);
    Route::post('/orders/{order}/cancellation-request', [OrderCancellationController::class, 'store']);
    Route::post('/orders/{order}/payments/iyzico/init', [IyzicoPaymentController::class, 'initialize']);
    Route::post('/orders/{order}/payments/stripe/init', [ApiStripePaymentController::class, 'initialize']);

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::get('/wishlist/ids', [WishlistController::class, 'ids']);
    Route::post('/wishlist/products/{product}', [WishlistController::class, 'store']);
    Route::delete('/wishlist/products/{product}', [WishlistController::class, 'destroy']);

    Route::get('/products/{product}/reviews/mine', [ProductReviewController::class, 'mine']);
    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store']);
    Route::put('/products/{product}/reviews/{review}', [ProductReviewController::class, 'update']);
    Route::delete('/products/{product}/reviews/{review}', [ProductReviewController::class, 'destroy']);

    Route::middleware('admin')->prefix('admin')->group(function (): void {
        Route::get('/summary', AdminSummaryController::class);
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update']);
        Route::get('/cancellation-requests', [AdminOrderCancellationController::class, 'index']);
        Route::post('/cancellation-requests/{cancellationRequest}/approve', [AdminOrderCancellationController::class, 'approve']);
        Route::post('/cancellation-requests/{cancellationRequest}/reject', [AdminOrderCancellationController::class, 'reject']);
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::get('/products/{product}', [AdminProductController::class, 'show']);
        Route::put('/products/{product}', [AdminProductController::class, 'update']);
        Route::post('/products/{product}/cover-image', [AdminProductController::class, 'uploadCover']);
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);
        Route::patch('/stocks/{stock}', [AdminStockController::class, 'update']);
    });
});
