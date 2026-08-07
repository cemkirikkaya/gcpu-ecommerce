<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\FakeStripePaymentGateway;
use App\Services\OrderService;
use App\Services\PaymentGatewayFactory;
use App\Services\StripePaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripePaymentController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentGatewayFactory $gatewayFactory,
    ) {}

    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        Log::info('Stripe webhook received');

        try {
            /** @var StripePaymentGateway $gateway */
            $gateway = $this->gatewayFactory->stripe();
            $constructed = $gateway->constructWebhookEvent($payload, $signature);
        } catch (\Throwable $exception) {
            Log::warning('Stripe webhook verification failed', [
                'error' => $exception->getMessage(),
            ]);

            return response($exception->getMessage(), 400);
        }

        $event = $constructed['event'];

        if ($event->type !== 'checkout.session.completed') {
            return response('OK', 200);
        }

        $session = $event->data->object;
        $orderId = (int) ($session->metadata['order_id'] ?? $session->client_reference_id ?? 0);

        $order = $orderId > 0
            ? Order::query()->find($orderId)
            : Order::query()
                ->where('stripe_checkout_session_id', $session->id)
                ->first();

        if ($order === null) {
            Log::warning('Stripe webhook order not found', [
                'session_id' => $session->id ?? null,
                'order_id' => $orderId,
            ]);

            return response('OK', 200);
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return response('OK', 200);
        }

        $result = $gateway->resultFromCheckoutSession($session);

        if ($result->successful) {
            $this->orderService->completePayment(
                $order,
                $result->paymentId,
                null,
                $result->paidPrice,
            );

            Log::info('Stripe webhook succeeded', [
                'order_id' => $order->id,
                'session_id' => $session->id ?? null,
            ]);
        } else {
            $this->orderService->failPayment($order);
        }

        return response('OK', 200);
    }

    public function fake(string $sessionId): RedirectResponse
    {
        if (! config('stripe.fake')) {
            abort(404);
        }

        $order = Order::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->firstOrFail();

        if ($order->payment_status !== PaymentStatus::Paid) {
            $gateway = $this->gatewayFactory->stripe();
            $result = $gateway->retrieve($sessionId);

            if ($result->successful) {
                $paidPrice = $result->paidPrice;

                if ($paidPrice === null && $gateway instanceof FakeStripePaymentGateway) {
                    $paidPrice = $gateway->paidPriceForOrder($order);
                }

                $this->orderService->completePayment(
                    $order,
                    $result->paymentId,
                    null,
                    $paidPrice,
                );
            } else {
                $this->orderService->failPayment($order);
            }
        }

        $successBase = (string) config('stripe.success_url');
        $query = http_build_query([
            'order_id' => $order->id,
            'status' => 'success',
        ]);

        return redirect()->away(
            $successBase.(str_contains($successBase, '?') ? '&' : '?').$query,
        );
    }
}
