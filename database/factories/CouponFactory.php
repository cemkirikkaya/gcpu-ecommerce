<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##')),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_order_amount' => null,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function fixed(float $amount = 50): static
    {
        return $this->state(fn (): array => [
            'type' => CouponType::Fixed,
            'value' => $amount,
        ]);
    }
}
