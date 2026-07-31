<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\CartRepository;

class CartService
{
    public function __construct(
        private CartRepository $repository
    ) {}

    public function getCartWithItems(User $user)
    {
        return $this->repository->getWithItems($user);
    }

    public function addItem(User $user, ProductVariant $productVariant, int $quantity): CartItem
    {
        $cart = $this->repository->getOrCreate($user);

        return $this->repository->addItem(
            $cart,
            $productVariant,
            $quantity
        );
    }

    public function updateItemQuantity(CartItem $cartItem, int $quantity): CartItem
    {
        return $this->repository->updateQuantity(
            $cartItem,
            $quantity
        );
    }

    public function removeItem(CartItem $cartItem): void
    {
        $this->repository->removeItem($cartItem);
    }
}
