<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IyzicoPaymentController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function initialize(Request $request, Order $order): JsonResponse
    {
        $this->authorize('pay', $order);

        try {
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
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
