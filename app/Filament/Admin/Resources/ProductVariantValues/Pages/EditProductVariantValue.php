<?php

namespace App\Filament\Admin\Resources\ProductVariantValues\Pages;

use App\Filament\Admin\Resources\ProductVariantValues\ProductVariantValueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductVariantValue extends EditRecord
{
    protected static string $resource = ProductVariantValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
