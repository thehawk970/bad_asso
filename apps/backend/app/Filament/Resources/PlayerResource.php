<?php

namespace App\Filament\Resources;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PlayerResource\Pages;
use App\Models\Player;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Joueurs';

    protected static ?string $modelLabel = 'joueur';

    protected static ?string $pluralModelLabel = 'joueurs';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')
                ->label('Prénom')
                ->required()
                ->maxLength(255),

            TextInput::make('last_name')
                ->label('Nom')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->nullable()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('phone')
                ->label('Téléphone')
                ->tel()
                ->nullable()
                ->maxLength(20),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Joueur')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name'])
                    ->getStateUsing(fn (Player $record) => $record->last_name . ' ' . $record->first_name),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->toggleable(),

                TextColumn::make('licenses_status')
                    ->label('Licence')
                    ->badge()
                    ->getStateUsing(function (Player $record): string {
                        $latest = $record->licenses()->latest()->first();
                        if (! $latest) {
                            return 'Aucune';
                        }

                        return $latest->status->label();
                    })
                    ->color(function (Player $record): string {
                        $latest = $record->licenses()->latest()->first();
                        if (! $latest) {
                            return 'gray';
                        }

                        return $latest->status->color();
                    }),

                TextColumn::make('payment_status')
                    ->label('Paiement')
                    ->badge()
                    ->getStateUsing(function (Player $record): string {
                        $latest = $record->payments()->latest()->first();
                        if (! $latest) {
                            return 'Aucun';
                        }

                        return $latest->status->label();
                    })
                    ->color(function (Player $record): string {
                        $latest = $record->payments()->latest()->first();
                        if (! $latest) {
                            return 'gray';
                        }

                        return $latest->status->color();
                    }),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('without_license')
                    ->label('Sans licence')
                    ->query(fn (Builder $q) => $q->whereDoesntHave('licenses')),

                Filter::make('pending_payment')
                    ->label('Paiement en attente')
                    ->query(fn (Builder $q) => $q->whereHas('payments', function (Builder $sub) {
                        $sub->where('status', PaymentStatus::Pending->value);
                    })),

                Filter::make('no_validated_license')
                    ->label('Licence non validée')
                    ->query(fn (Builder $q) => $q->whereDoesntHave('licenses', function (Builder $sub) {
                        $sub->where('status', LicenseStatus::Validated->value);
                    })),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlayers::route('/'),
            'create' => Pages\CreatePlayer::route('/create'),
            'view'   => Pages\ViewPlayer::route('/{record}'),
            'edit'   => Pages\EditPlayer::route('/{record}/edit'),
        ];
    }
}
