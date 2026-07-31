<?php

namespace App\Filament\Admin\Resources\CartItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CartItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Ürün')
                    ->searchable(),

                TextColumn::make('product.price')
                    ->label('Birim Fiyat')
                    ->money('TRY')
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Adet')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Ara Toplam')
                    ->state(fn ($record) => $record->subtotal())
                    ->money('TRY'),

                TextColumn::make('reserved_until')
                    ->label('Rezerve Bitiş')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Sepetten Kaldır'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
