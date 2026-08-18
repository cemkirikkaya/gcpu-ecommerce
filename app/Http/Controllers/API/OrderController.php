<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderInvoiceService;
use App\Services\OrderService;
use App\Support\PaymentProviderCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private OrderInvoiceService $orderInvoiceService,
    ) {}

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
            'latestCancellationRequest.user',
        ]);

        $payload = [
            'order' => new OrderResource($order),
        ];

        /** @var User $user */
        $user = Auth::user();

        if ($user->can('pay', $order)) {
            $payload['payment_options'] = [
                'direct_payment' => (bool) config('iyzico.direct'),
                'payment_providers' => PaymentProviderCatalog::available(),
            ];
        }

        return response()->json($payload);
    }

    public function invoice(Order $order): Response
    {
        $this->authorize('downloadInvoice', $order);

        return $this->orderInvoiceService
            ->generatePdf($order)
            ->download($this->orderInvoiceService->filename($order));
    }

    public function installments(Order $order): JsonResponse
    {
        $this->authorize('pay', $order);

        if (! config('iyzico.direct')) {
            return response()->json([
                'installments' => [],
                'direct_payment' => false,
            ]);
        }

        try {
            $options = $this->orderService->getInstallmentOptions((float) $order->total_price);

            return response()->json([
                'installments' => array_map(
                    fn ($option) => $option->toArray(),
                    $options,
                ),
                'direct_payment' => true,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
