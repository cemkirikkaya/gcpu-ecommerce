<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

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
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->paragraph(),
        ];
    }
}
