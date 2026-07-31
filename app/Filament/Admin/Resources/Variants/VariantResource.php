<?php

namespace App\Filament\Admin\Resources\Variants;

use App\Filament\Admin\Resources\Variants\Pages\CreateVariant;
use App\Filament\Admin\Resources\Variants\Pages\EditVariant;
use App\Filament\Admin\Resources\Variants\Pages\ListVariants;
use App\Filament\Admin\Resources\Variants\Schemas\VariantForm;
use App\Filament\Admin\Resources\Variants\Tables\VariantsTable;
use App\Models\Variant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VariantResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Variant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return VariantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VariantsTable::configure($table);
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
            'index' => ListVariants::route('/'),
            'create' => CreateVariant::route('/create'),
            'edit' => EditVariant::route('/{record}/edit'),
        ];
    }
}
