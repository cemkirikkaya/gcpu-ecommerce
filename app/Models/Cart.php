<?php

namespace App\Models;

use App\Services\CouponService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'coupon_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function total(): float
    {
        return max(0, round($this->subtotal() - $this->discountAmount(), 2));
    }

    public function subtotal(): float
    {
        $this->loadMissing('items');

        return $this->items
            ->sum(fn (CartItem $item): float => $item->subtotal());
    }

    public function discountAmount(): float
    {
        $this->loadMissing('coupon');

        if ($this->coupon === null) {
            return 0.0;
        }

        return app(CouponService::class)->calculateDiscount(
            $this->coupon,
            $this->subtotal(),
        );
    }
}
