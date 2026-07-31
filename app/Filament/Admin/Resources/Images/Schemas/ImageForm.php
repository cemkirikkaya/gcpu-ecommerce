<?php

namespace App\Filament\Admin\Resources\Images\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->required(),

                FileUpload::make('image')
                    ->directory('products')
                    ->image()
                    ->required(),

                Toggle::make('is_cover')
                    ->label('Kapak Görseli'),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

            ]);
    }
}
