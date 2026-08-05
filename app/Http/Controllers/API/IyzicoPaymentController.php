<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitIyzicoPaymentRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class IyzicoPaymentController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function initialize(InitIyzicoPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('pay', $order);

        Log::info('Payment init API request', [
            'order_id' => $order->id,
            'direct' => (bool) config('iyzico.direct'),
            'fake' => (bool) config('iyzico.fake'),
            'installment' => $request->installment(),
            'ip' => $request->ip(),
        ]);

        try {
            if (config('iyzico.direct')) {
                $this->orderService->chargePaymentDirectly(
                    $order,
                    $request->ip() ?? '127.0.0.1',
                    $request->installment(),
                );

                $query = http_build_query([
                    'order_id' => $order->id,
                    'status' => 'success',
                ]);

                return response()->json([
                    'redirect_url' => rtrim((string) config('iyzico.frontend_result_url'), '/').'?'.$query,
                ]);
            }

            $initialization = $this->orderService->initializePayment(
                $order,
                $request->ip() ?? '127.0.0.1',
            );

            return response()->json([
                'token' => $initialization->token,
                'payment_page_url' => $initialization->paymentPageUrl,
                'conversation_id' => $initialization->conversationId,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Payment init API failed', [
                'order_id' => $order->id,
                'direct' => (bool) config('iyzico.direct'),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
