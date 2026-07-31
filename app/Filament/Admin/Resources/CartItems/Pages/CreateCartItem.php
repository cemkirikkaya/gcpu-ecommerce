<?php

namespace App\Filament\Admin\Resources\CartItems\Pages;

use App\Filament\Admin\Resources\CartItems\CartItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCartItem extends CreateRecord
{
    protected static string $resource = CartItemResource::class;
}
