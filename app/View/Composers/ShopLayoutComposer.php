<?php

namespace App\View\Composers;

use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShopLayoutComposer
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function compose(View $view): void
    {
        $cartItemCount = 0;

        if (Auth::check() && Auth::user()->isCustomer()) {
            $cart = $this->cartService->getCartWithItems(Auth::user());
            $cartItemCount = $cart->items->sum('quantity');
        }

        $view->with([
            'shopName' => config('shop.name'),
            'cartItemCount' => $cartItemCount,
            'reservationMinutes' => config('shop.reservation_minutes'),
        ]);
    }
}
