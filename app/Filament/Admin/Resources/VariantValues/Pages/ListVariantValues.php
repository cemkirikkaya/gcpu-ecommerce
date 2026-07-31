<?php

namespace App\Filament\Admin\Resources\VariantValues\Pages;

use App\Filament\Admin\Resources\VariantValues\VariantValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVariantValues extends ListRecords
{
    protected static string $resource = VariantValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
