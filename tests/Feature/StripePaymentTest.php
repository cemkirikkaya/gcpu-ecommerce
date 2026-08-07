<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\User;
use App\Services\CartService;
use App\Services\FakeStripePaymentGateway;
use App\Services\OrderService;
use App\Services\PaymentGatewayFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function expect;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'stripe.fake' => true,
            'stripe.enabled' => true,
            'payments.providers.stripe.enabled' => true,
        ]);
    }

    #[Test]
    public function initializes_fake_stripe_payment_for_pending_order(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/payments/stripe/init")
            ->assertSuccessful()
            ->assertJsonStructure([
                'token',
                'payment_page_url',
                'session_id',
            ]);

        $order->refresh();

        expect($order->stripe_checkout_session_id)->not->toBeNull();
    }

    #[Test]
    public function completes_order_after_successful_stripe_fake_checkout(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $initResponse = $this->postJson("/api/orders/{$order->id}/payments/stripe/init")
            ->assertSuccessful();

        $sessionId = $initResponse->json('session_id');

        $this->get(route('payment.stripe.fake', ['sessionId' => $sessionId]))
            ->assertRedirect();

        $order->refresh();

        expect($order->payment_status->value)->toBe('paid')
            ->and($order->status->value)->toBe('processing')
            ->and(CartItem::query()->count())->toBe(0);
    }

    #[Test]
    public function marks_order_failed_when_stripe_fake_checkout_fails(): void
    {
        $customer = User::factory()->create();
        $order = $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $initResponse = $this->postJson("/api/orders/{$order->id}/payments/stripe/init")
            ->assertSuccessful();

        $sessionId = $initResponse->json('session_id');

        /** @var FakeStripePaymentGateway $gateway */
        $gateway = app(PaymentGatewayFactory::class)->stripe();
        $gateway->markSessionFailed($sessionId);

        $this->get(route('payment.stripe.fake', ['sessionId' => $sessionId]))
            ->assertRedirect();

        expect($order->fresh()->payment_status->value)->toBe('failed')
            ->and(CartItem::query()->count())->toBe(1);
    }

    #[Test]
    public function checkout_preview_includes_stripe_payment_provider(): void
    {
        $customer = User::factory()->create();
        $this->createPendingOrder($customer);

        Sanctum::actingAs($customer);

        $this->getJson('/api/checkout')
            ->assertSuccessful()
            ->assertJsonFragment(['id' => 'iyzico'])
            ->assertJsonFragment(['id' => 'stripe']);
    }

    private function createPendingOrder(User $customer): Order
    {
        $product = Product::query()->create([
            'name' => 'Monitör',
            'price' => 1200,
            'description' => 'Test',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'STRIPE-1',
        ]);

        Stock::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        app(CartService::class)->addItem($customer, $variant, 1);

        return app(OrderService::class)->checkout($customer);
    }
}
