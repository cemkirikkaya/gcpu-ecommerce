<?php

namespace App\Filament\Admin\Resources\ProductVariantValues\Pages;

use App\Filament\Admin\Resources\ProductVariantValues\ProductVariantValueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductVariantValue extends CreateRecord
{
    protected static string $resource = ProductVariantValueResource::class;
}
