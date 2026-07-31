<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Services\ProductCatalogService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['catalog_variants']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $catalogVariants = $this->form->getState()['catalog_variants'] ?? [];

        app(ProductCatalogService::class)->syncVariants(
            $this->getRecord(),
            $catalogVariants,
        );
    }
}
