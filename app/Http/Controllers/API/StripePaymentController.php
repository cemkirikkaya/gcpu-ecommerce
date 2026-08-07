<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripePaymentController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function initialize(Request $request, Order $order): JsonResponse
    {
        $this->authorize('pay', $order);

        Log::info('Stripe init API request', [
            'order_id' => $order->id,
            'fake' => (bool) config('stripe.fake'),
            'ip' => $request->ip(),
        ]);

        try {
            $initialization = $this->orderService->initializePayment(
                $order,
                $request->ip() ?? '127.0.0.1',
                PaymentProvider::Stripe,
            );

            return response()->json([
                'token' => $initialization->token,
                'payment_page_url' => $initialization->paymentPageUrl,
                'session_id' => $initialization->conversationId,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Stripe init API failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
