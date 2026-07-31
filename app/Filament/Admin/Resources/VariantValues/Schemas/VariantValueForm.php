<?php

namespace App\Filament\Admin\Resources\VariantValues\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VariantValueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('variant_id')
                    ->relationship('variant', 'name')
                    ->required(),
                TextInput::make('value')
                    ->required(),
            ]);
    }
}
