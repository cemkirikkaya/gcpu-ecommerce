<?php

namespace App\Filament\Admin\Resources\VariantValues\Pages;

use App\Filament\Admin\Resources\VariantValues\VariantValueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVariantValue extends EditRecord
{
    protected static string $resource = VariantValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
