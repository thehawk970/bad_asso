<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('player_id')
                ->label('Joueur')
                ->relationship('player', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->last_name . ' ' . $record->first_name)
                ->searchable()
                ->required(),

            TextInput::make('amount')
                ->label('Montant (€)')
                ->numeric()
                ->minValue(0)
                ->required(),

            Select::make('method')
                ->label('Méthode')
                ->options(collect(PaymentMethod::cases())->mapWithKeys(
                    fn (PaymentMethod $m) => [$m->value => $m->label()]
                ))
                ->required(),

            Select::make('status')
                ->label('Statut')
                ->options(collect(PaymentStatus::cases())->mapWithKeys(
                    fn (PaymentStatus $s) => [$s->value => $s->label()]
                ))
                ->required(),

            TextInput::make('reference')
                ->label('Référence (ex: HelloAsso)')
                ->nullable()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.last_name')
                    ->label('Joueur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, Payment $record) => $record->player->last_name . ' ' . $record->player->first_name),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->getStateUsing(fn (Payment $record) => $record->method->label()),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(fn (Payment $record) => $record->status->label())
                    ->color(fn (Payment $record) => $record->status->color()),

                TextColumn::make('reference')
                    ->label('Référence')
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('method')
                    ->label('Méthode')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m) => [$m->value => $m->label()]
                    )),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $s) => [$s->value => $s->label()]
                    )),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
