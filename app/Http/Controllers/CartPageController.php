<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartPageController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->getCartWithItems(
            Auth::user()
        );

        return view('cart.index', [
            'cart' => $cart,
            'reservationMinutes' => config('shop.reservation_minutes'),
        ]);
    }

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        try {
            $productVariant = ProductVariant::query()->findOrFail(
                $request->validated('product_variant_id')
            );

            $this->cartService->addItem(
                Auth::user(),
                $productVariant,
                $request->integer('quantity')
            );

            return redirect()
                ->route('cart.index')
                ->with('success', 'Ürün sepete eklendi ve '.config('shop.reservation_minutes').' dakika rezerve edildi.');

        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function update(
        UpdateCartItemRequest $request,
        CartItem $cartItem
    ): RedirectResponse {
        try {
            $this->cartService->updateItemQuantity(
                $cartItem,
                $request->integer('quantity')
            );

            return back()
                ->with('success', 'Sepet güncellendi ve rezervasyon süresi yenilendi.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function destroy(
        CartItem $cartItem
    ): RedirectResponse {
        $this->authorize('delete', $cartItem);

        $this->cartService->removeItem($cartItem);

        return back()
            ->with('success', 'Ürün sepetten kaldırıldı.');
    }
}
