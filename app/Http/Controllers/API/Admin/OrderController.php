<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\Api\AdminOrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(private AdminOrderService $adminOrderService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('adminViewAny', Order::class);

        /** @var User $user */
        $user = $request->user();

        $orders = $this->adminOrderService
            ->ordersQueryFor($user)
            ->with([
                'address',
                'items.cartItem.productVariant.product',
            ])
            ->get()
            ->map(function (Order $order) use ($user): AdminOrderResource {
                $items = $this->adminOrderService->itemsForUser($order, $user);

                return AdminOrderResource::forUser(
                    $order,
                    $user,
                    $items,
                    (float) $order->getRawOriginal('total_price'),
                );
            });

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('adminView', $order);

        /** @var User $user */
        $user = $request->user();

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
        ]);
    }

    public function update(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('adminUpdate', $order);

        /** @var User $user */
        $user = $request->user();

        try {
            $order = $this->adminOrderService->updateStatus(
                $order,
                $request->enum('status', OrderStatus::class),
            );
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
            'message' => 'Sipariş durumu güncellendi.',
        ]);
    }
}
