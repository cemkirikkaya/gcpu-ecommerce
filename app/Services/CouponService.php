<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()
            ->where('code', Coupon::normalizeCode($code))
            ->first();
    }

    public function validateForSubtotal(Coupon $coupon, float $subtotal): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'code' => 'Bu kupon artık geçerli değil.',
            ]);
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'code' => 'Bu kupon henüz kullanıma açılmadı.',
            ]);
        }

        if ($coupon->expires_at !== null && $coupon->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'Bu kuponun süresi dolmuş.',
            ]);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'code' => 'Bu kuponun kullanım limiti dolmuş.',
            ]);
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'code' => 'Bu kupon için minimum sepet tutarı sağlanmıyor.',
            ]);
        }
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $discount = match ($coupon->type) {
            CouponType::Percent => round($subtotal * ((float) $coupon->value / 100), 2),
            CouponType::Fixed => (float) $coupon->value,
        };

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return min($discount, $subtotal);
    }

    public function recordUsage(Coupon $coupon, Order $order): void
    {
        DB::transaction(function () use ($coupon): void {
            $lockedCoupon = Coupon::query()
                ->whereKey($coupon->id)
                ->lockForUpdate()
                ->first();

            if ($lockedCoupon === null) {
                return;
            }

            $lockedCoupon->increment('used_count');
        });
    }
}
