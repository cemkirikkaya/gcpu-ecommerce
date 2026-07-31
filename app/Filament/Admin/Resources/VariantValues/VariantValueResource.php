<?php

namespace App\Filament\Admin\Resources\VariantValues;

use App\Filament\Admin\Resources\VariantValues\Pages\CreateVariantValue;
use App\Filament\Admin\Resources\VariantValues\Pages\EditVariantValue;
use App\Filament\Admin\Resources\VariantValues\Pages\ListVariantValues;
use App\Filament\Admin\Resources\VariantValues\Schemas\VariantValueForm;
use App\Filament\Admin\Resources\VariantValues\Tables\VariantValuesTable;
use App\Models\VariantValue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VariantValueResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = VariantValue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'value';

    public static function form(Schema $schema): Schema
    {
        return VariantValueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VariantValuesTable::configure($table);
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
            'index' => ListVariantValues::route('/'),
            'create' => CreateVariantValue::route('/create'),
            'edit' => EditVariantValue::route('/{record}/edit'),
        ];
    }
}
