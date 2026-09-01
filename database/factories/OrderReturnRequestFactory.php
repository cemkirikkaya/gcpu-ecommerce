<?php

namespace Database\Factories;

use App\Enums\ReturnRequestStatus;
use App\Enums\ReturnRequestType;
use App\Models\OrderReturnRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReturnRequest>
 */
class OrderReturnRequestFactory extends Factory
{
    protected $model = OrderReturnRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ReturnRequestType::Return,
            'status' => ReturnRequestStatus::Pending,
            'message' => fake()->sentence(12),
        ];
    }

    public function exchange(): static
    {
        return $this->state(fn (): array => [
            'type' => ReturnRequestType::Exchange,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => ReturnRequestStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
