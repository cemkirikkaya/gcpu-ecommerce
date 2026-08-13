<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\InstallmentOption;
use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRefundResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;
use App\Support\StripeCheckoutData;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct()
    {
        Stripe::setApiKey((string) config('stripe.secret_key'));
    }

    public function initialize(Order $order, string $buyerIp): PaymentInitializationResult
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product',
            'cart.user',
        ]);

        $user = $order->cart?->user;

        if ($user === null) {
            throw new RuntimeException('Ödeme için sipariş bilgileri eksik.');
        }

        $successBase = (string) config('stripe.success_url');
        $successUrl = $successBase
            .(str_contains($successBase, '?') ? '&' : '?')
            .'session_id={CHECKOUT_SESSION_ID}&order_id='.$order->id;

        $cancelBase = (string) config('stripe.cancel_url');
        $cancelUrl = $cancelBase
            .(str_contains($cancelBase, '?') ? '&' : '?')
            .'order_id='.$order->id;

        try {
            $session = Session::create([
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $order->id,
                'customer_email' => $user->email,
                'line_items' => StripeCheckoutData::lineItems($order),
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ]);
        } catch (ApiErrorException $exception) {
            Log::error('stripe checkout session failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Stripe ödeme oturumu başlatılamadı.');
        }

        if ($session->id === null || $session->url === null) {
            throw new RuntimeException('Stripe ödeme oturumu başlatılamadı.');
        }

        Log::info('stripe checkout session created', [
            'order_id' => $order->id,
            'session_id' => $session->id,
        ]);

        return new PaymentInitializationResult(
            token: $session->id,
            paymentPageUrl: $session->url,
            conversationId: $session->id,
        );
    }

    public function retrieve(string $token): PaymentRetrievalResult
    {
        try {
            $session = Session::retrieve($token, [
                'expand' => ['payment_intent'],
            ]);
        } catch (ApiErrorException $exception) {
            Log::warning('stripe session retrieve failed', [
                'session_id' => $token,
                'error' => $exception->getMessage(),
            ]);

            return new PaymentRetrievalResult(
                successful: false,
                errorMessage: 'Stripe ödeme sonucu alınamadı.',
            );
        }

        return $this->resultFromSession($session);
    }

    /**
     * @return array{event: Event, type: string}
     */
    public function constructWebhookEvent(string $payload, string $signature): array
    {
        $secret = (string) config('stripe.webhook_secret');

        if ($secret === '') {
            throw new RuntimeException('Stripe webhook secret tanımlı değil.');
        }

        $event = Webhook::constructEvent($payload, $signature, $secret);

        return [
            'event' => $event,
            'type' => $event->type,
        ];
    }

    public function resultFromCheckoutSession(object $session): PaymentRetrievalResult
    {
        return $this->resultFromSession($session);
    }

    public function chargeDirectly(Order $order, string $buyerIp, int $installment = 1): PaymentRetrievalResult
    {
        throw new RuntimeException('Stripe doğrudan ödeme desteklemiyor.');
    }

    /**
     * @return list<InstallmentOption>
     */
    public function getInstallmentOptions(string $price, string $binNumber): array
    {
        throw new RuntimeException('Stripe taksit sorgusu desteklemiyor.');
    }

    public function refund(Order $order): PaymentRefundResult
    {
        if ($order->stripe_payment_intent_id === null) {
            return new PaymentRefundResult(
                successful: false,
                errorMessage: 'İade için ödeme referansı bulunamadı.',
            );
        }

        try {
            $refund = Refund::create([
                'payment_intent' => $order->stripe_payment_intent_id,
            ]);

            return new PaymentRefundResult(
                successful: true,
                refundReference: $refund->id,
            );
        } catch (ApiErrorException $exception) {
            Log::warning('Stripe refund failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return new PaymentRefundResult(
                successful: false,
                errorMessage: $exception->getMessage(),
            );
        }
    }

    private function resultFromSession(object $session): PaymentRetrievalResult
    {
        $paymentStatus = (string) ($session->payment_status ?? '');
        $successful = $paymentStatus === 'paid';

        $amountTotal = isset($session->amount_total) ? (int) $session->amount_total : null;
        $paymentIntentId = is_object($session->payment_intent ?? null)
            ? ($session->payment_intent->id ?? null)
            : ($session->payment_intent ?? null);

        return new PaymentRetrievalResult(
            successful: $successful,
            paymentId: $paymentIntentId ?? ($session->id ?? null),
            errorMessage: $successful ? null : 'Ödeme tamamlanamadı.',
            paidPrice: $amountTotal !== null
                ? StripeCheckoutData::formatAmount($amountTotal)
                : null,
        );
    }
}
