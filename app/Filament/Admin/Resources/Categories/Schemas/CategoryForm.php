<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label('Üst Kategori'),

                TextInput::make('name')
                    ->label('Kategori Adı')
                    ->required(),

                TextInput::make('slug')
                    ->required(),

                Textarea::make('description')
                    ->label('Açıklama')
                    ->columnSpanFull(),

                Select::make('variants')
                    ->label('Kullanılacak Varyantlar')
                    ->relationship('variants', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

            ]);
    }
}
