<?php

namespace App\Policies;

use App\Models\StockAlert;
use App\Models\User;

class StockAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function delete(User $user, StockAlert $stockAlert): bool
    {
        return $stockAlert->user_id === $user->id;
    }
}
