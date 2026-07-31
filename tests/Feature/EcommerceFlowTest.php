<?php

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists seeded catalog categories on the products page', function () {
    $this->seed();

    $this->get(route('products.index'))
        ->assertSuccessful()
        ->assertSee('Elektronik')
        ->assertSee('Nova X Pro')
        ->assertSee('Essential Pamuklu Tişört');
});

it('reserves stock for other customers when an item is in cart', function () {
    $product = Product::query()->create([
        'name' => 'Kulaklık',
        'price' => 500,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'HEADPHONE-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $firstCustomer = User::factory()->create();
    $secondCustomer = User::factory()->create();

    app(CartService::class)->addItem($firstCustomer, $variant, 2);

    expect($variant->fresh()->availableQuantity())->toBe(0);

    expect(fn () => app(CartService::class)->addItem($secondCustomer, $variant, 1))
        ->toThrow(RuntimeException::class, 'Yeterli stok bulunmamaktadır.');
});

it('extends reservation expiry when cart quantity is updated', function () {
    $product = Product::query()->create([
        'name' => 'Mouse',
        'price' => 150,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'MOUSE-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    $customer = User::factory()->create();
    $cartItem = app(CartService::class)->addItem($customer, $variant, 1);

    $originalExpiry = $cartItem->reserved_until;

    $this->travel(5)->minutes();

    app(CartService::class)->updateItemQuantity($cartItem->fresh(), 2);

    expect($cartItem->fresh()->reserved_until?->greaterThan($originalExpiry))->toBeTrue();
});

it('decrements stock after checkout and clears the cart', function () {
    $customer = User::factory()->create([
        'email' => 'buyer@example.com',
        'password' => 'password',
    ]);

    $product = Product::query()->create([
        'name' => 'Klavye',
        'price' => 1200,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'KEYBOARD-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 4,
    ]);

    app(CartService::class)->addItem($customer, $variant, 2);

    $order = app(OrderService::class)->checkout($customer);

    expect($order->total_price)->toBe('2400.00')
        ->and($variant->fresh()->stockQuantity())->toBe(2)
        ->and(CartItem::query()->count())->toBe(0);
});

it('completes checkout from the storefront flow', function () {
    $customer = User::factory()->create([
        'email' => 'shopper@example.com',
        'password' => 'password',
    ]);

    $product = Product::query()->create([
        'name' => 'Monitör',
        'price' => 3000,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'MONITOR-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->actingAs($customer)
        ->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])
        ->assertRedirect(route('cart.index'));

    $this->actingAs($customer)
        ->post(route('checkout.store'), [
            'first_name' => 'Ali',
            'last_name' => 'Veli',
            'address_line_1' => 'Test Sokak 1',
            'city' => 'İstanbul',
            'postal_code' => '34000',
            'country' => 'Türkiye',
        ])
        ->assertRedirect();

    expect($variant->fresh()->stockQuantity())->toBe(0);
});

it('removes expired reservations via the scheduled command', function () {
    $customer = User::factory()->create();

    $product = Product::query()->create([
        'name' => 'Webcam',
        'price' => 800,
        'description' => 'Test',
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'WEBCAM-1',
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $this->travel(config('shop.reservation_minutes') + 1)->minutes();

    $this->artisan('reservations:clear')->assertSuccessful();

    expect(CartItem::query()->count())->toBe(0)
        ->and($variant->fresh()->availableQuantity())->toBe(3);
});
