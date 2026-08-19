<?php

use App\Enums\OrderStatus;
use App\Jobs\CreateOrderShipmentJob;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\GeliverTrackingStatusMapper;
use App\Services\OrderService;
use App\Services\OrderShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createPaidOrderForShipment(array $addressOverrides = []): Order
{
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();

    Address::factory()->for($customer)->create([
        'is_default' => true,
    ]);

    $product = Product::query()->create([
        'user_id' => $vendor->id,
        'name' => 'Kargo Test Ürün',
        'price' => 500,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'GELIVER-TEST-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->completePayment($order);

    if ($addressOverrides !== []) {
        $order->address?->update($addressOverrides);
    }

    return $order->fresh(['address']);
}

it('dispatches automatic shipment creation after payment', function () {
    Bus::fake();

    config(['geliver.auto_create_on_payment' => true]);

    $order = createPaidOrderForShipment();

    Bus::assertDispatched(CreateOrderShipmentJob::class, function (CreateOrderShipmentJob $job) use ($order) {
        return $job->order->is($order);
    });
});

it('creates shipment automatically when payment completes with sync queue', function () {
    Mail::fake();

    config([
        'geliver.auto_create_on_payment' => true,
        'geliver.fake' => true,
    ]);

    $order = createPaidOrderForShipment();

    expect($order->fresh())
        ->status->toBe(OrderStatus::Shipped)
        ->geliver_shipment_id->not->toBeNull()
        ->tracking_number->not->toBeNull()
        ->tracking_url->not->toBeNull();

    Mail::assertQueued(OrderShippedMail::class);
});

it('lets platform admin create a geliver shipment for a processing order', function () {
    Mail::fake();

    config(['geliver.auto_create_on_payment' => false]);

    $order = createPaidOrderForShipment();
    $order->update(['status' => OrderStatus::Processing]);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->postJson("/api/admin/orders/{$order->id}/shipment")
        ->assertCreated()
        ->assertJsonPath('message', 'Kargo oluşturuldu.')
        ->assertJsonPath('order.status', OrderStatus::Shipped->value)
        ->assertJsonPath('order.tracking_number', fn ($value) => filled($value))
        ->assertJsonPath('order.tracking_url', fn ($value) => filled($value))
        ->assertJsonPath('order.geliver_shipment_id', fn ($value) => filled($value));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Shipped)
        ->and($order->geliver_shipment_id)->not->toBeNull()
        ->and($order->tracking_number)->not->toBeNull()
        ->and($order->tracking_url)->not->toBeNull();

    Mail::assertQueued(OrderShippedMail::class);
});

it('creates shipment from pending order and moves it to shipped', function () {
    Mail::fake();

    config(['geliver.auto_create_on_payment' => false]);

    $order = createPaidOrderForShipment();

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->postJson("/api/admin/orders/{$order->id}/shipment")
        ->assertCreated()
        ->assertJsonPath('order.status', OrderStatus::Shipped->value);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

it('rejects duplicate shipment creation', function () {
    config(['geliver.auto_create_on_payment' => false]);

    $order = createPaidOrderForShipment();
    $order->update([
        'status' => OrderStatus::Processing,
        'geliver_shipment_id' => 'existing-shipment',
    ]);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->postJson("/api/admin/orders/{$order->id}/shipment")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Bu sipariş için kargo zaten oluşturulmuş.');
});

it('requires phone number on delivery address', function () {
    config(['geliver.auto_create_on_payment' => false]);

    $order = createPaidOrderForShipment(['phone' => null]);
    $order->update(['status' => OrderStatus::Processing]);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->postJson("/api/admin/orders/{$order->id}/shipment")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Teslimat adresinde telefon numarası zorunludur.');
});

it('forbids vendors from creating shipments', function () {
    config(['geliver.auto_create_on_payment' => false]);

    $order = createPaidOrderForShipment();
    $order->update(['status' => OrderStatus::Processing]);

    $this->withToken(User::factory()->vendor()->create()->createToken('test')->plainTextToken)
        ->postJson("/api/admin/orders/{$order->id}/shipment")
        ->assertForbidden();
});

it('exposes tracking fields on customer order detail', function () {
    $order = createPaidOrderForShipment();
    $order->update([
        'status' => OrderStatus::Shipped,
        'tracking_number' => 'TRK123456',
        'tracking_url' => 'https://tracking.example.test/TRK123456',
        'geliver_shipment_id' => 'shipment-123',
    ]);

    $customer = $order->user();

    $this->withToken($customer->createToken('test')->plainTextToken)
        ->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('order.tracking_number', 'TRK123456')
        ->assertJsonPath('order.tracking_url', 'https://tracking.example.test/TRK123456');
});

it('updates tracking info from geliver webhook', function () {
    $order = createPaidOrderForShipment();
    $order->update([
        'status' => OrderStatus::Shipped,
        'geliver_shipment_id' => 'geliver-shipment-42',
    ]);

    $this->postJson('/api/webhooks/geliver', [
        'event' => 'TRACK_UPDATED',
        'data' => [
            'id' => 'geliver-shipment-42',
            'trackingNumber' => 'UPDATED123',
            'trackingUrl' => 'https://tracking.example.test/UPDATED123',
            'trackingStatus' => [
                'trackingStatusCode' => 'IN_TRANSIT',
            ],
        ],
    ])->assertOk();

    $order->refresh();

    expect($order->tracking_number)->toBe('UPDATED123')
        ->and($order->tracking_url)->toBe('https://tracking.example.test/UPDATED123')
        ->and($order->status)->toBe(OrderStatus::Shipped);
});

it('marks order as delivered from geliver webhook', function () {
    Mail::fake();

    $order = createPaidOrderForShipment();
    $order->update([
        'status' => OrderStatus::Shipped,
        'geliver_shipment_id' => 'geliver-shipment-99',
    ]);

    $this->postJson('/api/webhooks/geliver', [
        'event' => 'TRACK_UPDATED',
        'data' => [
            'id' => 'geliver-shipment-99',
            'statusCode' => 'DELIVERED',
            'trackingStatus' => [
                'trackingStatusCode' => 'DELIVERED',
            ],
        ],
    ])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);

    Mail::assertQueued(OrderDeliveredMail::class);
});

it('maps geliver tracking codes to order statuses', function () {
    $mapper = app(GeliverTrackingStatusMapper::class);

    expect($mapper->resolveOrderStatus([
        'statusCode' => 'SHIPPED',
    ]))->toBe(OrderStatus::Shipped);

    expect($mapper->resolveOrderStatus([
        'trackingStatus' => ['trackingStatusCode' => 'DELIVERED'],
    ]))->toBe(OrderStatus::Delivered);
});

it('syncs order status through the shipment service webhook handler', function () {
    Mail::fake();

    $order = createPaidOrderForShipment();
    $order->update([
        'status' => OrderStatus::Shipped,
        'geliver_shipment_id' => 'geliver-shipment-sync',
    ]);

    app(OrderShipmentService::class)->syncFromWebhook($order, [
        'trackingNumber' => 'SYNC123',
        'trackingUrl' => 'https://tracking.example.test/SYNC123',
        'statusCode' => 'DELIVERED',
        'trackingStatus' => [
            'trackingStatusCode' => 'DELIVERED',
        ],
    ]);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->tracking_number)->toBe('SYNC123');

    Mail::assertQueued(OrderDeliveredMail::class);
});
