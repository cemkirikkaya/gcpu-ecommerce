<?php

namespace App\Filament\Admin\Resources\Variants\Pages;

use App\Filament\Admin\Resources\Variants\VariantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVariants extends ListRecords
{
    protected static string $resource = VariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
