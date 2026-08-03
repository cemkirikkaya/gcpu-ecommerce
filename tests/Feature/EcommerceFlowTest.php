<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

use function expect;

class EcommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lists_seeded_catalog_categories_on_the_products_page(): void
    {
        $this->seed();

        $this->get(route('products.index'))
            ->assertSuccessful()
            ->assertSee('Elektronik')
            ->assertSee('Nova X Pro')
            ->assertSee('Essential Pamuklu Tişört');
    }

    #[Test]
    public function reserves_stock_for_other_customers_when_an_item_is_in_cart(): void
    {
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

        $this->assertSame(0, $variant->fresh()->availableQuantity());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Yeterli stok bulunmamaktadır.');

        app(CartService::class)->addItem($secondCustomer, $variant, 1);
    }

    #[Test]
    public function extends_reservation_expiry_when_cart_quantity_is_updated(): void
    {
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
    }

    #[Test]
    public function decrements_stock_after_checkout_and_clears_the_cart(): void
    {
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
        app(OrderService::class)->completePayment($order);

        expect($order->total_price)->toBe('2400.00')
            ->and($variant->fresh()->stockQuantity())->toBe(2)
            ->and(CartItem::query()->count())->toBe(0);
    }

    #[Test]
    public function returns_an_order_through_the_api_for_the_owning_customer(): void
    {
        $customer = User::factory()->create();

        $product = Product::query()->create([
            'name' => 'Klavye',
            'price' => 1200,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'KEYBOARD-API-1',
        ]);

        Stock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 4,
        ]);

        app(CartService::class)->addItem($customer, $variant, 2);

        $order = app(OrderService::class)->checkout($customer);
        app(OrderService::class)->completePayment($order);

        Sanctum::actingAs($customer);

        $this->getJson("/api/orders/{$order->id}")
            ->assertSuccessful()
            ->assertJsonPath('order.id', $order->id)
            ->assertJsonPath('order.total_price', 2400)
            ->assertJsonPath('order.status_label', 'Hazırlanıyor')
            ->assertJsonPath('order.items.0.product_name', 'Klavye');
    }

    #[Test]
    public function lists_only_the_authenticated_customers_orders_through_the_api(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $product = Product::query()->create([
            'name' => 'Klavye',
            'price' => 1200,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'KEYBOARD-LIST-1',
        ]);

        Stock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        app(CartService::class)->addItem($customer, $variant, 1);
        $customerOrder = app(OrderService::class)->checkout($customer);
        app(OrderService::class)->completePayment($customerOrder);

        app(CartService::class)->addItem($otherCustomer, $variant, 1);
        $otherOrder = app(OrderService::class)->checkout($otherCustomer);
        app(OrderService::class)->completePayment($otherOrder);

        Sanctum::actingAs($customer);

        $this->getJson('/api/orders')
            ->assertSuccessful()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $customerOrder->id)
            ->assertJsonPath('orders.0.status', 'processing');
    }

    #[Test]
    public function forbids_viewing_another_customers_order_through_the_api(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $product = Product::query()->create([
            'name' => 'Klavye',
            'price' => 1200,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'KEYBOARD-FORBID-1',
        ]);

        Stock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        app(CartService::class)->addItem($otherCustomer, $variant, 1);
        $otherOrder = app(OrderService::class)->checkout($otherCustomer);

        Sanctum::actingAs($customer);

        $this->getJson("/api/orders/{$otherOrder->id}")
            ->assertForbidden();
    }

    #[Test]
    public function completes_checkout_from_the_storefront_flow(): void
    {
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

        $this->assertSame(1, $variant->fresh()->stockQuantity());

        $order = Order::query()->firstOrFail();
        app(OrderService::class)->completePayment($order);

        $this->assertSame(0, $variant->fresh()->stockQuantity());
    }

    #[Test]
    public function removes_expired_reservations_via_the_scheduled_command(): void
    {
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
    }
}
