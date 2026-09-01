<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProductReviewService
{
    public function canReview(User $user, Product $product): bool
    {
        if (! $user->isCustomer()) {
            return false;
        }

        return $this->hasDeliveredPurchase($user, $product);
    }

    public function hasDeliveredPurchase(User $user, Product $product): bool
    {
        return OrderItem::query()
            ->whereHas('cartItem.productVariant', fn (Builder $variantQuery) => $variantQuery
                ->where('product_id', $product->id))
            ->whereHas('order', fn (Builder $orderQuery) => $orderQuery
                ->whereIn('payment_status', [
                    PaymentStatus::Paid,
                    PaymentStatus::Refunded,
                    PaymentStatus::PartiallyRefunded,
                ])
                ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Returned])
                ->whereHas('cart', fn (Builder $cartQuery) => $cartQuery
                    ->where('user_id', $user->id)))
            ->exists();
    }
}
