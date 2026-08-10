<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::query()->create([
                'user_id' => User::factory()->vendor()->create()->id,
                'name' => fake()->words(3, true),
                'price' => fake()->randomFloat(2, 100, 5000),
            ])->id,
        ];
    }
}
