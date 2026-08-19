<?php

namespace App\Services;

use App\Mail\BackInStockMail;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Mail;

class BackInStockService
{
    public function evaluateVariant(ProductVariant $variant, ?int $previousQuantity = null): void
    {
        $variant->loadMissing(['stock', 'product']);

        $currentQuantity = $variant->stock?->quantity ?? 0;
        $previousQuantity ??= $currentQuantity;

        if ($currentQuantity === 0) {
            $this->resetNotificationState($variant);

            return;
        }

        if ($previousQuantity > 0 || $currentQuantity <= 0) {
            return;
        }

        $this->notifySubscribers($variant);
    }

    private function notifySubscribers(ProductVariant $variant): void
    {
        StockAlert::query()
            ->with(['user', 'productVariant.product'])
            ->where('product_variant_id', $variant->id)
            ->whereNull('notified_at')
            ->get()
            ->each(function (StockAlert $alert) use ($variant): void {
                $user = $alert->user;

                if ($user === null || blank($user->email)) {
                    return;
                }

                Mail::to($user)->send(new BackInStockMail($user, $alert->productVariant ?? $variant));

                $alert->update(['notified_at' => now()]);
            });
    }

    private function resetNotificationState(ProductVariant $variant): void
    {
        StockAlert::query()
            ->where('product_variant_id', $variant->id)
            ->update(['notified_at' => null]);
    }
}
