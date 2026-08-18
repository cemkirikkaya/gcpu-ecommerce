<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderInvoiceService
{
    public function loadOrderForInvoice(Order $order): Order
    {
        $order->load([
            'items.cartItem.productVariant.product',
            'items.cartItem.productVariant.variantValues.variantValue.variant',
            'address',
            'cart.user',
        ]);

        return $order;
    }

    public function filename(Order $order): string
    {
        return "fatura-siparis-{$order->id}.pdf";
    }

    public function generatePdf(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order = $this->loadOrderForInvoice($order);

        return Pdf::loadView('invoices.order', [
            'order' => $order,
            'customer' => $order->user(),
            'paidTotal' => (float) ($order->paid_price ?? $order->total_price),
            'paidAt' => $order->paid_at,
            'shop' => config('shop'),
        ])->setPaper('a4');
    }
}
