<?php

namespace Database\Factories;

use App\Models\OrderReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReturnItem>
 */
class OrderReturnItemFactory extends Factory
{
    protected $model = OrderReturnItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quantity' => 1,
            'replacement_product_variant_id' => null,
        ];
    }
}
