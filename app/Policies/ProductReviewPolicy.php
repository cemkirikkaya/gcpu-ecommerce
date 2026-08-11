<?php

namespace App\Policies;

use App\Models\ProductReview;
use App\Models\User;

class ProductReviewPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, ProductReview $productReview): bool
    {
        return $user->isCustomer() && $productReview->user_id === $user->id;
    }

    public function delete(User $user, ProductReview $productReview): bool
    {
        return $user->isCustomer() && $productReview->user_id === $user->id;
    }
}
