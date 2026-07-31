<?php

namespace App\Filament\Admin\Resources\ProductVariants\Pages;

use App\Filament\Admin\Resources\ProductVariants\ProductVariantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductVariant extends CreateRecord
{
    protected static string $resource = ProductVariantResource::class;
}
