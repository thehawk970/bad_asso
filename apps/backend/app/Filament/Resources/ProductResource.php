<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Produits';

    protected static ?string $modelLabel = 'produit';

    protected static ?string $pluralModelLabel = 'produits';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom du produit')
                ->required()
                ->maxLength(255)
                ->placeholder('ex: Licence, Volant plume…'),

            TextInput::make('price')
                ->label('Prix (€)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->required()
                ->suffix('€'),

            Textarea::make('description')
                ->label('Description')
                ->nullable()
                ->rows(2),

            Toggle::make('is_active')
                ->label('Disponible à la vente')
                ->default(true),

            Toggle::make('is_license_product')
                ->label('Ce produit est une licence')
                ->helperText('Quand une commande contenant ce produit est payée, la licence du joueur est validée automatiquement.')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Prix')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Disponible')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('is_license_product')
                    ->label('Licence')
                    ->boolean()
                    ->trueIcon('heroicon-o-identification')
                    ->trueColor('info')
                    ->falseIcon('heroicon-o-minus')
                    ->falseColor('gray'),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Disponibles uniquement')
                    ->query(fn (Builder $q) => $q->where('is_active', true))
                    ->default(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Product $record) => $record->orderItems()->exists()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
