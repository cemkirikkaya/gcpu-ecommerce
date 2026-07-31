<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Models\Address;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCartWithItems(Auth::user());

        if ($cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Ödeme için sepetinizde ürün olmalı.');
        }

        $addresses = Auth::user()->addresses()->latest('is_default')->latest()->get();

        return view('checkout.index', [
            'cart' => $cart,
            'addresses' => $addresses,
            'reservationMinutes' => config('shop.reservation_minutes'),
        ]);
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
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

            $order = $this->orderService->checkout(
                Auth::user(),
                $addressId,
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Siparişiniz başarıyla oluşturuldu.');
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }
}
