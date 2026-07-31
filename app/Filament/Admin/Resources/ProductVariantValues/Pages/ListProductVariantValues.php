<?php

namespace App\Filament\Admin\Resources\ProductVariantValues\Pages;

use App\Filament\Admin\Resources\ProductVariantValues\ProductVariantValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductVariantValues extends ListRecords
{
    protected static string $resource = ProductVariantValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
