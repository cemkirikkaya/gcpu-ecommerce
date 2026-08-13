<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderCancellationRequest;
use App\Http\Resources\Api\OrderCancellationRequestResource;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Services\OrderCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class OrderCancellationController extends Controller
{
    public function __construct(private OrderCancellationService $cancellationService) {}

    public function store(StoreOrderCancellationRequest $request, Order $order): JsonResponse
    {
        $this->authorize('create', [OrderCancellationRequest::class, $order]);

        try {
            $cancellationRequest = $this->cancellationService->request(
                $order,
                Auth::user(),
                $request->validated('message'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $cancellationRequest->load(['user']);

        return response()->json([
            'cancellation_request' => new OrderCancellationRequestResource($cancellationRequest),
            'message' => 'İptal talebiniz alındı. Satıcı ve yönetici bilgilendirilecek.',
        ], 201);
    }
}
