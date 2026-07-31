<?php

namespace App\Filament\Admin\Resources\Stocks;

use App\Filament\Admin\Resources\Stocks\Pages\CreateStock;
use App\Filament\Admin\Resources\Stocks\Pages\EditStock;
use App\Filament\Admin\Resources\Stocks\Pages\ListStocks;
use App\Filament\Admin\Resources\Stocks\Schemas\StockForm;
use App\Filament\Admin\Resources\Stocks\Tables\StocksTable;
use App\Models\Stock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Stock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'sku';

    public static function form(Schema $schema): Schema
    {
        return StockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StocksTable::configure($table);
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
            'index' => ListStocks::route('/'),
            'create' => CreateStock::route('/create'),
            'edit' => EditStock::route('/{record}/edit'),
        ];
    }
}
