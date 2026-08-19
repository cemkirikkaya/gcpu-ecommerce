<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdminOrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminOrderService;
use App\Services\OrderShipmentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class OrderShipmentController extends Controller
{
    public function __construct(
        private OrderShipmentService $orderShipmentService,
        private AdminOrderService $adminOrderService,
    ) {}

    public function store(Order $order): JsonResponse
    {
        $this->authorize('adminCreateShipment', $order);

        /** @var User $user */
        $user = request()->user();

        try {
            $order = $this->orderShipmentService->createShipment($order);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $order->load([
            'address',
            'items.cartItem.productVariant.product.vendor',
            'items.cartItem.productVariant.variantValues.variantValue.variant',
        ]);

        return response()->json([
            'order' => AdminOrderResource::forUser(
                $order,
                $user,
                $this->adminOrderService->itemsForUser($order, $user),
                (float) $order->getRawOriginal('total_price'),
            ),
            'message' => 'Kargo oluşturuldu.',
        ], 201);
    }
}
