<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function view(User $user, Order $order): bool
    {
        return $order->cart?->user_id === $user->id;
    }

    public function pay(User $user, Order $order): bool
    {
        if (! $this->view($user, $order)) {
            return false;
        }

        return in_array($order->payment_status, [
            PaymentStatus::Pending,
            PaymentStatus::Failed,
        ], true);
    }
}
