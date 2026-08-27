<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\ApplyCartCouponRequest;
use App\Http\Resources\Api\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartCouponController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function store(ApplyCartCouponRequest $request): JsonResponse
    {
        try {
            $cart = $this->cartService->applyCoupon(
                Auth::user(),
                $request->string('code')->toString(),
            );

            return response()->json([
                'message' => 'Kupon uygulandı.',
                'cart' => new CartResource($cart),
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function destroy(): JsonResponse
    {
        $cart = $this->cartService->removeCoupon(Auth::user());

        return response()->json([
            'message' => 'Kupon kaldırıldı.',
            'cart' => new CartResource($cart),
        ]);
    }
}
