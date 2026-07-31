<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Variant;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kategori')
                    ->description('Kategori seçin veya yeni kategori oluşturun. Alt kategori için üst kategori de seçebilirsiniz.')
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Select::make('parent_id')
                                    ->label('Üst Kategori')
                                    ->relationship('parent', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                TextInput::make('name')
                                    ->label('Kategori Adı')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $data['slug'] ??= Str::slug($data['name']);

                                return Category::query()->create($data)->id;
                            }),

                        Select::make('base_variant_id')
                            ->label('Liste Varyantı (Gruplama)')
                            ->helperText('Mağazada ürünler bu varyanta göre gruplanır. Örn: Renk, Model')
                            ->options(fn (): array => Variant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Ürün Bilgileri')
                    ->schema([
                        TextInput::make('name')
                            ->label('Ürün Adı')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('price')
                            ->label('Temel Fiyat (₺)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(4)
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('product_images')
                            ->label('Ürün Görselleri')
                            ->collection('product-images')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Satış Seçenekleri')
                    ->description('Renk, hafıza, model, stok ve varyanta özel görsel bilgilerini tek formda girin.')
                    ->schema([
                        Repeater::make('catalog_variants')
                            ->label('')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('stock')
                                    ->label('Stok')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('color')
                                    ->label('Renk')
                                    ->maxLength(255),

                                TextInput::make('memory')
                                    ->label('Hafıza')
                                    ->placeholder('128 GB')
                                    ->maxLength(255),

                                TextInput::make('model')
                                    ->label('Model')
                                    ->maxLength(255),

                                FileUpload::make('image')
                                    ->label('Varyant Görseli')
                                    ->directory('products/variants')
                                    ->image()
                                    ->columnSpanFull(),

                                Toggle::make('is_cover')
                                    ->label('Kapak Görseli')
                                    ->default(false),

                                Repeater::make('extra_attributes')
                                    ->label('Ek Özellikler')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Özellik Adı')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('value')
                                            ->label('Değer')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->addActionLabel('Ek özellik ekle')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Yeni satış seçeneği ekle')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
