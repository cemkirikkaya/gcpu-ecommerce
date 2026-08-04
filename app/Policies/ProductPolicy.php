<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminApi();
    }

    public function view(User $user, Product $product): bool
    {
        return $this->ownsOrManagesAll($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdminApi();
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownsOrManagesAll($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->ownsOrManagesAll($user, $product);
    }

    private function ownsOrManagesAll(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isVendor() && $product->user_id === $user->id;
    }
}
