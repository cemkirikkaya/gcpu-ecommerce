<?php

namespace App\Support;

use App\Models\Order;

class StripeCheckoutData
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function lineItems(Order $order): array
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product',
        ]);

        $currency = (string) config('stripe.currency');
        $items = [];

        foreach ($order->items as $item) {
            $product = $item->cartItem?->productVariant?->product;
            $name = $product?->name ?? 'Ürün';

            $items[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $name,
                    ],
                    'unit_amount' => self::amountInMinorUnits((float) $item->price),
                ],
                'quantity' => $item->quantity,
            ];
        }

        if ($items === []) {
            $items[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Sipariş #'.$order->id,
                    ],
                    'unit_amount' => self::amountInMinorUnits((float) $order->total_price),
                ],
                'quantity' => 1,
            ];
        }

        return $items;
    }

    public static function amountInMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function formatAmount(int $minorUnits): string
    {
        return number_format($minorUnits / 100, 2, '.', '');
    }
}
