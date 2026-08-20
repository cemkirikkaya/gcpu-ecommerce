<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCrossSellProduct(User $vendor, string $name): Product
{
    return Product::query()->create([
        'user_id' => $vendor->id,
        'name' => $name,
        'price' => 1500,
        'description' => 'Test',
    ]);
}

function createPaidOrderWithProducts(User $customer, Product ...$products): void
{
    foreach ($products as $product) {
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'CROSS-'.fake()->unique()->bothify('??-####'),
        ]);

        Stock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        app(CartService::class)->addItem($customer, $variant, 1);
    }

    $order = app(OrderService::class)->checkout($customer);
    app(OrderService::class)->chargePaymentDirectly($order, '127.0.0.1');
}

it('returns products frequently bought together with the source product', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $primaryProduct = createCrossSellProduct($vendor, 'Ana Ürün');
    $crossSellProduct = createCrossSellProduct($vendor, 'Birlikte Alınan Ürün');
    $otherProduct = createCrossSellProduct($vendor, 'Başka Ürün');

    createPaidOrderWithProducts($customer, $primaryProduct, $crossSellProduct);
    createPaidOrderWithProducts($customer, $primaryProduct, $otherProduct);
    createPaidOrderWithProducts($customer, $primaryProduct, $crossSellProduct);

    $this->getJson("/api/products/{$primaryProduct->id}/cross-sell")
        ->assertOk()
        ->assertJsonCount(2, 'products')
        ->assertJsonPath('products.0.name', 'Birlikte Alınan Ürün')
        ->assertJsonPath('products.1.name', 'Başka Ürün');
});

it('excludes the source product from cross-sell results', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $product = createCrossSellProduct($vendor, 'Tek Ürün');

    createPaidOrderWithProducts($customer, $product);

    $this->getJson("/api/products/{$product->id}/cross-sell")
        ->assertOk()
        ->assertJsonCount(0, 'products');
});

it('ignores unpaid orders when calculating cross-sell products', function () {
    $vendor = User::factory()->vendor()->create();
    $customer = User::factory()->create();
    $primaryProduct = createCrossSellProduct($vendor, 'Ana Ürün');
    $crossSellProduct = createCrossSellProduct($vendor, 'Ödenmemiş Sipariş Ürünü');

    $variant = ProductVariant::query()->create([
        'product_id' => $crossSellProduct->id,
        'sku' => 'UNPAID-'.fake()->unique()->bothify('??-####'),
    ]);

    Stock::query()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $variant, 1);

    $primaryVariant = ProductVariant::query()->create([
        'product_id' => $primaryProduct->id,
        'sku' => 'PRIMARY-'.fake()->unique()->bothify('??-####'),
    ]);

    Stock::query()->create([
        'product_variant_id' => $primaryVariant->id,
        'quantity' => 5,
    ]);

    app(CartService::class)->addItem($customer, $primaryVariant, 1);
    app(OrderService::class)->checkout($customer);

    $this->getJson("/api/products/{$primaryProduct->id}/cross-sell")
        ->assertOk()
        ->assertJsonCount(0, 'products');
});
