<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartPageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\IyzicoPaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StripePaymentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/cart', [CartPageController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartPageController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{cartItem}', [CartPageController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [CartPageController::class, 'destroy'])->name('cart.items.destroy');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders/{order}/pay', [IyzicoPaymentController::class, 'initialize'])
        ->name('payment.iyzico.init');

    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

Route::post('/payment/iyzico/callback', [IyzicoPaymentController::class, 'callback'])
    ->name('payment.iyzico.callback');

Route::get('/payment/iyzico/fake/{token}', [IyzicoPaymentController::class, 'fake'])
    ->name('payment.iyzico.fake');

Route::post('/payment/stripe/webhook', [StripePaymentController::class, 'webhook'])
    ->name('payment.stripe.webhook');

Route::get('/payment/stripe/fake/{sessionId}', [StripePaymentController::class, 'fake'])
    ->name('payment.stripe.fake');
