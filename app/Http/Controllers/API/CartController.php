<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\Api\CartResource;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function show(): JsonResponse
    {
        $cart = $this->cartService->getCartWithItems(Auth::user());

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        try {
            $productVariant = ProductVariant::query()->findOrFail(
                $request->validated('product_variant_id')
            );

            $this->cartService->addItem(
                Auth::user(),
                $productVariant,
                $request->integer('quantity'),
            );

            $cart = $this->cartService->getCartWithItems(Auth::user());

            return response()->json([
                'message' => 'Ürün sepete eklendi.',
                'cart' => new CartResource($cart),
            ], 201);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        try {
            $this->cartService->updateItemQuantity(
                $cartItem,
                $request->integer('quantity'),
            );

            $cart = $this->cartService->getCartWithItems(Auth::user());

            return response()->json([
                'message' => 'Sepet güncellendi.',
                'cart' => new CartResource($cart),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function destroy(CartItem $cartItem): JsonResponse
    {
        $this->authorize('delete', $cartItem);

        $this->cartService->removeItem($cartItem);

        $cart = $this->cartService->getCartWithItems(Auth::user());

        return response()->json([
            'message' => 'Ürün sepetten kaldırıldı.',
            'cart' => new CartResource($cart),
        ]);
    }
}
