<?php

use App\Enums\CancellationRequestStatus;
use App\Enums\OrderStatus;
use App\Mail\OrderCancellationApprovedMail;
use App\Mail\OrderCancellationRejectedMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\OrderCancellationRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\AdminOrderService;
use App\Services\CartService;
use App\Services\OrderCancellationService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createPaidOrderForLifecycleMail(): array
{
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Kargo Test Ürün',
        'price' => 750,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'LIFE-MAIL-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');

    return compact('customer', 'order');
}

it('queues a shipped email when order status becomes shipped', function () {
    Mail::fake();

    ['customer' => $customer, 'order' => $order] = createPaidOrderForLifecycleMail();
    $order->update(['status' => OrderStatus::Processing]);

    app(AdminOrderService::class)->updateStatus($order->fresh(), OrderStatus::Shipped);

    Mail::assertQueued(OrderShippedMail::class, function (OrderShippedMail $mail) use ($customer, $order) {
        return $mail->hasTo($customer->email)
            && $mail->order->is($order->fresh());
    });
});

it('queues a delivered email when order status becomes delivered', function () {
    Mail::fake();

    ['customer' => $customer, 'order' => $order] = createPaidOrderForLifecycleMail();
    $order->update(['status' => OrderStatus::Shipped]);

    app(AdminOrderService::class)->updateStatus($order->fresh(), OrderStatus::Delivered);

    Mail::assertQueued(OrderDeliveredMail::class, function (OrderDeliveredMail $mail) use ($customer, $order) {
        return $mail->hasTo($customer->email)
            && $mail->order->is($order->fresh());
    });
});

it('does not queue lifecycle emails when order status is unchanged', function () {
    ['order' => $order] = createPaidOrderForLifecycleMail();
    $order->update(['status' => OrderStatus::Shipped]);

    Mail::fake();

    app(AdminOrderService::class)->updateStatus($order->fresh(), OrderStatus::Shipped);

    Mail::assertNothingQueued();
});

it('queues a cancellation approved email after admin approval', function () {
    Mail::fake();

    ['customer' => $customer, 'order' => $order] = createPaidOrderForLifecycleMail();
    $admin = User::factory()->admin()->create();

    $request = OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'İptal talebi test mesajı.',
        'status' => CancellationRequestStatus::Pending,
    ]);

    app(OrderCancellationService::class)->approve($request, $admin, 'Onaylandı, iade başlatıldı.');

    Mail::assertQueued(OrderCancellationApprovedMail::class, function (OrderCancellationApprovedMail $mail) use ($customer, $order) {
        return $mail->hasTo($customer->email)
            && $mail->cancellationRequest->order_id === $order->id;
    });
});

it('queues a cancellation rejected email after admin rejection', function () {
    Mail::fake();

    ['customer' => $customer, 'order' => $order] = createPaidOrderForLifecycleMail();
    $admin = User::factory()->admin()->create();

    $request = OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'İptal talebi test mesajı.',
        'status' => CancellationRequestStatus::Pending,
    ]);

    app(OrderCancellationService::class)->reject($request, $admin, 'Sipariş kargoya verildi, iptal edilemez.');

    Mail::assertQueued(OrderCancellationRejectedMail::class, function (OrderCancellationRejectedMail $mail) use ($customer, $order) {
        return $mail->hasTo($customer->email)
            && $mail->cancellationRequest->order_id === $order->id;
    });
});

it('includes order details in shipped email content', function () {
    ['customer' => $customer, 'order' => $order] = createPaidOrderForLifecycleMail();

    $mailable = new OrderShippedMail($order->fresh([
        'items.cartItem.productVariant.product',
        'address',
        'cart.user',
    ]));

    $mailable->assertSeeInHtml('Kargo Test Ürün');
    $mailable->assertSeeInHtml('#'.$order->id);
    $mailable->assertSeeInHtml($customer->name);
});
