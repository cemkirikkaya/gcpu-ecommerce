<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WishlistItem;

class WishlistItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function delete(User $user, WishlistItem $wishlistItem): bool
    {
        return $wishlistItem->user_id === $user->id;
    }
}
