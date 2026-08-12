<?php

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createPendingOrderForMail(User $customer): Order
{
    $product = Product::query()->create([
        'name' => 'Kablosuz Kulaklık',
        'price' => 1499,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'MAIL-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    return app(OrderService::class)->checkout($customer);
}

it('queues an order confirmation email after successful payment', function () {
    Mail::fake();

    $customer = User::factory()->create();
    $order = createPendingOrderForMail($customer);

    app(OrderService::class)->completePayment($order, 'pay_test_123');

    Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($customer, $order) {
        return $mail->hasTo($customer->email)
            && $mail->order->is($order->fresh());
    });
});

it('does not queue duplicate confirmation emails for already paid orders', function () {
    Mail::fake();

    $customer = User::factory()->create();
    $order = createPendingOrderForMail($customer);
    $orderService = app(OrderService::class);

    $orderService->completePayment($order, 'pay_test_123');
    $orderService->completePayment($order->fresh(), 'pay_test_123');

    Mail::assertQueued(OrderConfirmationMail::class, 1);
});

it('includes order details in confirmation email content', function () {
    $customer = User::factory()->create(['name' => 'Ayşe Yılmaz']);
    $order = createPendingOrderForMail($customer);

    app(OrderService::class)->completePayment($order, 'pay_test_123');

    $mailable = new OrderConfirmationMail($order->fresh([
        'items.cartItem.productVariant.product',
        'address',
        'cart.user',
    ]));

    $mailable->assertSeeInHtml('Kablosuz Kulaklık');
    $mailable->assertSeeInHtml('#'.$order->id);
    $mailable->assertSeeInHtml('Ayşe Yılmaz');
    $mailable->assertSeeInHtml('1.499,00');
});
