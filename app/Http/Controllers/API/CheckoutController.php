<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Http\Resources\Api\AddressResource;
use App\Http\Resources\Api\CartResource;
use App\Http\Resources\Api\OrderResource;
use App\Models\Address;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
    ) {}

    public function show(): JsonResponse
    {
        $cart = $this->cartService->getCartWithItems(Auth::user());

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Ödeme için sepetinizde ürün olmalı.',
            ], 422);
        }

        $addresses = Auth::user()->addresses()->latest('is_default')->latest()->get();

        return response()->json([
            'cart' => new CartResource($cart),
            'addresses' => AddressResource::collection($addresses),
            'reservation_minutes' => config('shop.reservation_minutes'),
            'direct_payment' => (bool) config('iyzico.direct'),
        ]);
    }

    public function installments(): JsonResponse
    {
        if (! config('iyzico.direct')) {
            return response()->json([
                'installments' => [],
                'direct_payment' => false,
            ]);
        }

        $cart = $this->cartService->getCartWithItems(Auth::user());

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Taksit seçenekleri için sepetinizde ürün olmalı.',
            ], 422);
        }

        try {
            $options = $this->orderService->getInstallmentOptions($cart->total());

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

    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        try {
            $addressId = $request->validated('address_id');

            if ($addressId === null) {
                $address = Address::query()->create([
                    'user_id' => Auth::id(),
                    'title' => 'Teslimat',
                    'first_name' => $request->string('first_name')->toString(),
                    'last_name' => $request->string('last_name')->toString(),
                    'phone' => $request->string('phone')->toString() ?: null,
                    'address_line_1' => $request->string('address_line_1')->toString(),
                    'address_line_2' => $request->string('address_line_2')->toString() ?: null,
                    'city' => $request->string('city')->toString(),
                    'state' => $request->string('state')->toString() ?: null,
                    'postal_code' => $request->string('postal_code')->toString(),
                    'country' => $request->string('country')->toString(),
                    'is_default' => Auth::user()->addresses()->doesntExist(),
                ]);

                $addressId = $address->id;
            }

            $order = $this->orderService->checkout(Auth::user(), $addressId);

            return response()->json([
                'message' => 'Siparişiniz oluşturuldu. Ödeme adımına geçebilirsiniz.',
                'order' => new OrderResource($order),
            ], 201);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
