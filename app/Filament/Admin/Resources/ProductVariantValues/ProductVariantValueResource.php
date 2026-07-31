<?php

namespace App\Filament\Admin\Resources\ProductVariantValues;

use App\Filament\Admin\Resources\ProductVariantValues\Pages\CreateProductVariantValue;
use App\Filament\Admin\Resources\ProductVariantValues\Pages\EditProductVariantValue;
use App\Filament\Admin\Resources\ProductVariantValues\Pages\ListProductVariantValues;
use App\Filament\Admin\Resources\ProductVariantValues\Schemas\ProductVariantValueForm;
use App\Filament\Admin\Resources\ProductVariantValues\Tables\ProductVariantValuesTable;
use App\Models\ProductVariantValue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductVariantValueResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = ProductVariantValue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'variant_value_id';

    public static function form(Schema $schema): Schema
    {
        return ProductVariantValueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductVariantValuesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductVariantValues::route('/'),
            'create' => CreateProductVariantValue::route('/create'),
            'edit' => EditProductVariantValue::route('/{record}/edit'),
        ];
    }
}
