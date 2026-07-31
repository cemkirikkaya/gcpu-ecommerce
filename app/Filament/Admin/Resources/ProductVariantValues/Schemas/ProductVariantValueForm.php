<?php

namespace App\Filament\Admin\Resources\ProductVariantValues\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ProductVariantValueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('product_variant_id')
                    ->relationship('productVariant', 'sku')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('variant_value_id')
                    ->relationship('variantValue', 'value')
                    ->searchable()
                    ->preload()
                    ->required(),

            ]);
    }
}
