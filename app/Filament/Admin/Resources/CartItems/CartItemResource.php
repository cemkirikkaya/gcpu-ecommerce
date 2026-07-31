<?php

namespace App\Filament\Admin\Resources\CartItems;

use App\Filament\Admin\Resources\CartItems\Pages\CreateCartItem;
use App\Filament\Admin\Resources\CartItems\Pages\EditCartItem;
use App\Filament\Admin\Resources\CartItems\Pages\ListCartItems;
use App\Filament\Admin\Resources\CartItems\Schemas\CartItemForm;
use App\Filament\Admin\Resources\CartItems\Tables\CartItemsTable;
use App\Models\CartItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CartItemResource extends Resource
{
    protected static ?string $model = CartItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CartItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CartItemsTable::configure($table);
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
            'index' => ListCartItems::route('/'),
            'create' => CreateCartItem::route('/create'),
            'edit' => EditCartItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('cart', function (Builder $query) {
                $query->where('user_id', Auth::id());
            });
    }
}
