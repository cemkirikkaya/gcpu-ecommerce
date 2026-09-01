<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnRequestStatus;
use App\Enums\ReturnRequestType;
use App\Mail\OrderReturnApprovedMail;
use App\Mail\OrderReturnCompletedMail;
use App\Models\Address;
use App\Models\OrderReturnRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createDeliveredOrderForReturn(int $quantity = 1): array
{
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    Address::factory()->for($customer)->default()->create();

    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'İade Test Ürün',
        'price' => 400,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'RETURN-TEST-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
    ]);

    app(CartService::class)->addItem($customer, $variant, $quantity);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');

    $order->update([
        'status' => OrderStatus::Delivered,
        'delivered_at' => now(),
    ]);

    $order->load(['items', 'address']);

    return compact('vendor', 'customer', 'order', 'variant', 'product');
}

it('lets customers request a return for a delivered order', function () {
    ['customer' => $customer, 'order' => $order] = createDeliveredOrderForReturn();

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/return-request", [
            'type' => ReturnRequestType::Return->value,
            'message' => 'Ürün beklentimi karşılamadı, iade etmek istiyorum.',
            'items' => [
                [
                    'order_item_id' => $order->items->first()->id,
                    'quantity' => 1,
                ],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('return_request.status', ReturnRequestStatus::Pending->value)
        ->assertJsonPath('return_request.type', ReturnRequestType::Return->value);

    expect(OrderReturnRequest::query()->count())->toBe(1);
});

it('rejects return requests for orders that are not delivered', function () {
    ['customer' => $customer, 'order' => $order] = createDeliveredOrderForReturn();

    $order->update(['status' => OrderStatus::Processing]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/return-request", [
            'type' => ReturnRequestType::Return->value,
            'message' => 'Henüz teslim olmadan iade etmek istiyorum.',
            'items' => [
                [
                    'order_item_id' => $order->items->first()->id,
                    'quantity' => 1,
                ],
            ],
        ])
        ->assertUnprocessable();
});

it('rejects return requests outside the return window', function () {
    ['customer' => $customer, 'order' => $order] = createDeliveredOrderForReturn();

    $order->update(['delivered_at' => now()->subDays(20)]);

    $response = $this->withToken($customer->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/return-request", [
            'type' => ReturnRequestType::Return->value,
            'message' => 'İade süresinden sonra talep oluşturmayı deniyorum.',
            'items' => [
                [
                    'order_item_id' => $order->items->first()->id,
                    'quantity' => 1,
                ],
            ],
        ]);

    $response->assertUnprocessable();
    expect($response->json('message'))->toContain('İade süresi');
});

it('shows pending return requests to the product vendor', function () {
    ['vendor' => $vendor, 'customer' => $customer, 'order' => $order] = createDeliveredOrderForReturn();

    OrderReturnRequest::factory()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Beden olmadı, iade etmek istiyorum.',
    ])->items()->create([
        'order_item_id' => $order->items->first()->id,
        'quantity' => 1,
    ]);

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->getJson('/api/admin/return-requests?status=pending')
        ->assertOk()
        ->assertJsonPath('pending_count', 1)
        ->assertJsonCount(1, 'return_requests')
        ->assertJsonPath('return_requests.0.message', 'Beden olmadı, iade etmek istiyorum.');
});

it('approves a return request and issues a shipping label', function () {
    Mail::fake();

    ['customer' => $customer, 'order' => $order] = createDeliveredOrderForReturn();
    $admin = User::factory()->admin()->create();

    $request = OrderReturnRequest::factory()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Kargo etiketi için onay bekliyorum.',
    ]);
    $request->items()->create([
        'order_item_id' => $order->items->first()->id,
        'quantity' => 1,
    ]);

    $response = $this->withToken($admin->createToken('test')->plainTextToken)
        ->postJson("/api/admin/return-requests/{$request->id}/approve")
        ->assertOk()
        ->assertJsonPath('return_request.status', ReturnRequestStatus::Approved->value);

    expect($response->json('return_request.return_label_url'))->not->toBeEmpty();

    Mail::assertQueued(OrderReturnApprovedMail::class);
});

it('forbids vendors from approving return requests', function () {
    ['vendor' => $vendor, 'customer' => $customer, 'order' => $order] = createDeliveredOrderForReturn();

    $request = OrderReturnRequest::factory()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Satıcı onaylamamalı.',
    ]);

    $this->withToken($vendor->createToken('test')->plainTextToken)
        ->postJson("/api/admin/return-requests/{$request->id}/approve")
        ->assertForbidden();
});

it('receives a return, restores stock and refunds payment', function () {
    Mail::fake();

    ['customer' => $customer, 'order' => $order, 'variant' => $variant] = createDeliveredOrderForReturn();
    $admin = User::factory()->admin()->create();
    $stockBefore = $variant->fresh()->stock?->quantity;

    $request = OrderReturnRequest::factory()->approved()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Ürün depoda, iade işlemini tamamla.',
        'return_label_url' => 'https://tracking.example.test/labels/test.pdf',
    ]);
    $request->items()->create([
        'order_item_id' => $order->items->first()->id,
        'quantity' => 1,
    ]);

    $this->withToken($admin->createToken('test')->plainTextToken)
        ->postJson("/api/admin/return-requests/{$request->id}/receive")
        ->assertOk()
        ->assertJsonPath('return_request.status', ReturnRequestStatus::Completed->value);

    $order->refresh();
    $request->refresh();

    expect($order->status)->toBe(OrderStatus::Returned)
        ->and($order->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($request->refund_reference)->not->toBeNull()
        ->and($variant->fresh()->stock?->quantity)->toBe($stockBefore + 1);

    Mail::assertQueued(OrderReturnCompletedMail::class);
});

it('keeps a partial return as delivered and partially refunded', function () {
    ['customer' => $customer, 'order' => $order, 'variant' => $variant] = createDeliveredOrderForReturn(2);
    $admin = User::factory()->admin()->create();
    $stockBefore = $variant->fresh()->stock?->quantity;

    $request = OrderReturnRequest::factory()->approved()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'İki üründen birini iade etmek istiyorum.',
        'return_label_url' => 'https://tracking.example.test/labels/partial.pdf',
    ]);
    $request->items()->create([
        'order_item_id' => $order->items->first()->id,
        'quantity' => 1,
    ]);

    $this->withToken($admin->createToken('test')->plainTextToken)
        ->postJson("/api/admin/return-requests/{$request->id}/receive")
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($variant->fresh()->stock?->quantity)->toBe($stockBefore + 1)
        ->and((float) $request->fresh()->refund_amount)->toBe(400.0);
});

it('completes an exchange by restocking and shipping a replacement', function () {
    ['customer' => $customer, 'order' => $order, 'variant' => $variant] = createDeliveredOrderForReturn();
    $admin = User::factory()->admin()->create();
    $stockBefore = $variant->fresh()->stock?->quantity;

    $request = OrderReturnRequest::factory()->exchange()->approved()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Aynı üründen yenisi ile değişim istiyorum.',
        'return_label_url' => 'https://tracking.example.test/labels/exchange.pdf',
    ]);
    $request->items()->create([
        'order_item_id' => $order->items->first()->id,
        'quantity' => 1,
        'replacement_product_variant_id' => $variant->id,
    ]);

    $response = $this->withToken($admin->createToken('test')->plainTextToken)
        ->postJson("/api/admin/return-requests/{$request->id}/receive")
        ->assertOk()
        ->assertJsonPath('return_request.status', ReturnRequestStatus::Completed->value);

    expect($response->json('return_request.exchange_tracking_number'))->not->toBeEmpty();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($variant->fresh()->stock?->quantity)->toBe($stockBefore);
});

it('includes return requests on customer order detail', function () {
    ['customer' => $customer, 'order' => $order] = createDeliveredOrderForReturn();

    $request = OrderReturnRequest::factory()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'message' => 'Müşteri iade mesajı burada.',
    ]);
    $request->items()->create([
        'order_item_id' => $order->items->first()->id,
        'quantity' => 1,
    ]);

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('order.return_requests.0.status', ReturnRequestStatus::Pending->value)
        ->assertJsonPath('order.return_requests.0.message', 'Müşteri iade mesajı burada.')
        ->assertJsonPath('order.items.0.returnable_quantity', 0);
});
