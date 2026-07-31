<?php

namespace App\Filament\Admin\Resources\Images;

use App\Filament\Admin\Resources\Images\Pages\CreateImage;
use App\Filament\Admin\Resources\Images\Pages\EditImage;
use App\Filament\Admin\Resources\Images\Pages\ListImages;
use App\Filament\Admin\Resources\Images\Schemas\ImageForm;
use App\Filament\Admin\Resources\Images\Tables\ImagesTable;
use App\Models\Image;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImageResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Image::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'image';

    public static function form(Schema $schema): Schema
    {
        return ImageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImagesTable::configure($table);
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
            'index' => ListImages::route('/'),
            'create' => CreateImage::route('/create'),
            'edit' => EditImage::route('/{record}/edit'),
        ];
    }
}
