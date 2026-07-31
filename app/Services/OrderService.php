<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;

class OrderService
{
    public function __construct(
        private OrderRepository $repository,
    ) {}

    public function checkout(User $user, ?int $addressId = null): Order
    {
        return $this->repository->checkout($user, $addressId);
    }
}
