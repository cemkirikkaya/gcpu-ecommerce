<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\FakePaymentGateway;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function expect;

class IyzicoPaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function initializes_fake_iyzico_payment_for_pending_order(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/payments/iyzico/init")
            ->assertSuccessful()
            ->assertJsonStructure([
                'token',
                'payment_page_url',
                'conversation_id',
            ]);

        expect($order->fresh()->iyzico_token)->not->toBeNull();
    }

    #[Test]
    public function completes_order_after_successful_iyzico_callback(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $initResponse = $this->postJson("/api/orders/{$order->id}/payments/iyzico/init")
            ->assertSuccessful();

        $token = $initResponse->json('token');

        $this->post(route('payment.iyzico.callback'), ['token' => $token])
            ->assertRedirect();

        $order->refresh();

        expect($order->payment_status->value)->toBe('paid')
            ->and($order->status->value)->toBe('processing')
            ->and(CartItem::query()->count())->toBe(0);
    }

    #[Test]
    public function completes_order_with_direct_fake_payment(): void
    {
        config(['iyzico.direct' => true]);

        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/payments/iyzico/init")
            ->assertSuccessful()
            ->assertJsonStructure(['redirect_url'])
            ->assertJsonMissing(['token', 'payment_page_url']);

        $order->refresh();

        expect($order->payment_status->value)->toBe('paid')
            ->and($order->status->value)->toBe('processing')
            ->and($order->installment)->toBe(1)
            ->and($order->iyzico_payment_id)->not->toBeNull()
            ->and(CartItem::query()->count())->toBe(0);
    }

    #[Test]
    public function completes_order_with_direct_fake_payment_and_installment(): void
    {
        config(['iyzico.direct' => true]);

        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/payments/iyzico/init", [
            'installment' => 3,
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['redirect_url']);

        /** @var FakePaymentGateway $gateway */
        $gateway = app(PaymentGateway::class);

        expect($gateway->lastInstallment)->toBe(3);

        $order->refresh();

        expect($order->installment)->toBe(3)
            ->and($order->iyzico_payment_id)->not->toBeNull();
    }

    #[Test]
    public function returns_installment_options_for_direct_checkout(): void
    {
        config(['iyzico.direct' => true]);

        $customer = User::factory()->create();
        $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $this->getJson('/api/checkout/installments')
            ->assertSuccessful()
            ->assertJsonStructure([
                'installments' => [
                    '*' => ['number', 'label', 'monthly_price', 'total_price'],
                ],
                'direct_payment',
            ])
            ->assertJsonPath('direct_payment', true);
    }

    #[Test]
    public function keeps_cart_items_when_payment_callback_fails(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $initResponse = $this->postJson("/api/orders/{$order->id}/payments/iyzico/init")
            ->assertSuccessful();

        $token = $initResponse->json('token');

        /** @var FakePaymentGateway $gateway */
        $gateway = app(PaymentGateway::class);
        $gateway->markTokenFailed($token);

        $this->post(route('payment.iyzico.callback'), ['token' => $token])
            ->assertRedirect();

        expect($order->fresh()->payment_status->value)->toBe('failed')
            ->and(CartItem::query()->count())->toBe(1);
    }

    private function createPendingOrder(User $customer): Order
    {
        $product = Product::query()->create([
            'name' => 'Kulaklık',
            'price' => 500,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'IYZICO-1',
        ]);

        Stock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        app(CartService::class)->addItem($customer, $variant, 1);

        return app(OrderService::class)->checkout($customer);
    }
}
