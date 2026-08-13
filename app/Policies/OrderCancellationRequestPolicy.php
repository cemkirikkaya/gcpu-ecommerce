<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\User;
use App\Services\OrderCancellationService;

class OrderCancellationRequestPolicy
{
    public function create(User $user, Order $order): bool
    {
        return $order->cart?->user_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminApi() || $user->isCustomer();
    }

    public function view(User $user, OrderCancellationRequest $orderCancellationRequest): bool
    {
        return app(OrderCancellationService::class)->canViewRequest($user, $orderCancellationRequest);
    }

    public function approve(User $user, OrderCancellationRequest $orderCancellationRequest): bool
    {
        return $user->isAdmin() && $orderCancellationRequest->isPending();
    }

    public function reject(User $user, OrderCancellationRequest $orderCancellationRequest): bool
    {
        return $user->isAdmin() && $orderCancellationRequest->isPending();
    }
}
