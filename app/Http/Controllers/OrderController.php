<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);

        $order->load([
            'items.cartItem.productVariant.product',
            'items.cartItem.productVariant.variantValues.variantValue.variant',
            'address',
        ]);

        return view('orders.show', compact('order'));
    }
}
