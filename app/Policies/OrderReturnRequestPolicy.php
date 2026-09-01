<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\User;
use App\Services\OrderReturnService;

class OrderReturnRequestPolicy
{
    public function create(User $user, Order $order): bool
    {
        return $order->cart?->user_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminApi() || $user->isCustomer();
    }

    public function view(User $user, OrderReturnRequest $orderReturnRequest): bool
    {
        return app(OrderReturnService::class)->canViewRequest($user, $orderReturnRequest);
    }

    public function approve(User $user, OrderReturnRequest $orderReturnRequest): bool
    {
        return $user->isAdmin() && $orderReturnRequest->isPending();
    }

    public function reject(User $user, OrderReturnRequest $orderReturnRequest): bool
    {
        return $user->isAdmin() && $orderReturnRequest->isPending();
    }

    public function receive(User $user, OrderReturnRequest $orderReturnRequest): bool
    {
        return $user->isAdmin() && $orderReturnRequest->isApproved();
    }
}
