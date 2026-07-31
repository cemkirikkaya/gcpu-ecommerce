<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Services\ProductCatalogService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['catalog_variants'] = app(ProductCatalogService::class)
            ->catalogVariantsFromProduct($this->getRecord());

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['catalog_variants']);

        return $data;
    }

    protected function afterSave(): void
    {
        $catalogVariants = $this->form->getState()['catalog_variants'] ?? [];

        app(ProductCatalogService::class)->syncVariants(
            $this->getRecord(),
            $catalogVariants,
        );
    }
}
