<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->whereHas('cart', fn ($query) => $query->where('user_id', Auth::id()))
            ->with(['address'])
            ->latest()
            ->get();

        return response()->json([
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load([
            'items.cartItem.productVariant.product',
            'items.cartItem.productVariant.variantValues.variantValue.variant',
            'address',
        ]);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }
}
