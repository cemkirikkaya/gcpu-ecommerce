<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReturnRequestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderReturnRequest;
use App\Http\Resources\Api\OrderReturnRequestResource;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Services\OrderReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class OrderReturnController extends Controller
{
    public function __construct(private OrderReturnService $returnService) {}

    public function store(StoreOrderReturnRequest $request, Order $order): JsonResponse
    {
        $this->authorize('create', [OrderReturnRequest::class, $order]);

        try {
            $returnRequest = $this->returnService->request(
                $order,
                Auth::user(),
                ReturnRequestType::from($request->validated('type')),
                $request->validated('message'),
                $request->validated('items'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'return_request' => new OrderReturnRequestResource($returnRequest),
            'message' => $returnRequest->isExchange()
                ? 'Değişim talebiniz alındı. Onay sonrası iade kargo etiketi hazırlanacak.'
                : 'İade talebiniz alındı. Onay sonrası iade kargo etiketi hazırlanacak.',
        ], 201);
    }
}
