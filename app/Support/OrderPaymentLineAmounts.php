<?php

namespace App\Support;

use App\Models\Order;

class OrderPaymentLineAmounts
{
    /**
     * @return list<float>
     */
    public static function forOrder(Order $order): array
    {
        $order->loadMissing('items');

        $lineSubtotals = $order->items
            ->map(fn ($item): float => $item->subtotal())
            ->values()
            ->all();

        if ($lineSubtotals === []) {
            return [(float) $order->total_price];
        }

        $subtotal = array_sum($lineSubtotals);
        $discount = max(0, round($subtotal - (float) $order->total_price, 2));

        if ($discount <= 0) {
            return $lineSubtotals;
        }

        return self::allocateDiscount($lineSubtotals, $discount);
    }

    /**
     * @param  list<float>  $lineSubtotals
     * @return list<float>
     */
    private static function allocateDiscount(array $lineSubtotals, float $discount): array
    {
        $subtotal = array_sum($lineSubtotals);
        $targetTotal = max(0, round($subtotal - $discount, 2));
        $allocated = [];
        $runningTotal = 0.0;

        foreach ($lineSubtotals as $index => $lineSubtotal) {
            $isLast = $index === count($lineSubtotals) - 1;

            if ($isLast) {
                $allocated[] = round($targetTotal - $runningTotal, 2);

                continue;
            }

            $share = round($lineSubtotal / $subtotal * $targetTotal, 2);
            $allocated[] = $share;
            $runningTotal += $share;
        }

        return $allocated;
    }
}
