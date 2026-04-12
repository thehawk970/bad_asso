<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PlayerResource\Pages;
use App\Models\Player;
use App\Models\Season;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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

    // ─── Formulaire ─────────────────────────────────────────────────────────────

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

    // ─── Infolist (page Vue) ─────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations')
                ->columns(2)
                ->schema([
                    TextEntry::make('first_name')->label('Prénom'),
                    TextEntry::make('last_name')->label('Nom'),
                    TextEntry::make('email')->label('Email')->default('—'),
                    TextEntry::make('phone')->label('Téléphone')->default('—'),
                    TextEntry::make('created_at')->label('Inscrit le')->dateTime('d/m/Y'),
                ]),

            Section::make('Licences')
                ->schema([
                    RepeatableEntry::make('licenses')
                        ->label('')
                        ->schema([
                            TextEntry::make('season.name')->label('Saison'),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label())
                                ->color(fn ($state) => $state->color()),
                            TextEntry::make('created_at')->label('Créée le')->dateTime('d/m/Y'),
                        ])
                        ->columns(3),
                ]),

            Section::make('Paiements')
                ->schema([
                    RepeatableEntry::make('payments')
                        ->label('')
                        ->schema([
                            TextEntry::make('amount')->label('Montant')->money('EUR'),
                            TextEntry::make('method')
                                ->label('Méthode')
                                ->formatStateUsing(fn ($state) => $state->label()),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label())
                                ->color(fn ($state) => $state->color()),
                            TextEntry::make('reference')->label('Référence')->default('—'),
                            TextEntry::make('created_at')->label('Date')->dateTime('d/m/Y'),
                        ])
                        ->columns(5),
                ]),
        ]);
    }

    // ─── Table ───────────────────────────────────────────────────────────────────

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

                        return $latest ? $latest->status->label() : 'Aucune';
                    })
                    ->color(function (Player $record): string {
                        $latest = $record->licenses()->latest()->first();

                        return $latest ? $latest->status->color() : 'gray';
                    }),

                TextColumn::make('payment_status')
                    ->label('Paiement')
                    ->badge()
                    ->getStateUsing(function (Player $record): string {
                        $latest = $record->payments()->latest()->first();

                        return $latest ? $latest->status->label() : 'Aucun';
                    })
                    ->color(function (Player $record): string {
                        $latest = $record->payments()->latest()->first();

                        return $latest ? $latest->status->color() : 'gray';
                    }),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('without_current_season_license')
                    ->label(function (): string {
                        $season = Season::current();
                        return $season ? "Sans licence {$season->name}" : 'Sans licence saison active';
                    })
                    ->query(function (Builder $q): Builder {
                        $season = Season::current();
                        if (! $season) {
                            return $q;
                        }
                        return $q->whereDoesntHave('licenses', function (Builder $sub) use ($season) {
                            $sub->where('season_id', $season->id);
                        });
                    }),

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
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('renew')
                    ->label('Renouveler')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Renouveler la licence ?')
                    ->modalDescription(function () {
                        $season = Season::current();

                        return $season
                            ? "Une nouvelle licence sera créée pour la saison {$season->name}."
                            : 'Aucune saison active. Activez une saison d\'abord.';
                    })
                    ->disabled(fn () => ! Season::current())
                    ->action(function (Player $record) {
                        $season = Season::current();

                        if (! $season) {
                            Notification::make()->title('Aucune saison active')->warning()->send();
                            return;
                        }

                        $license = app(\App\Services\LicenseService::class)->renewForPlayer($record, $season);

                        if (! $license) {
                            Notification::make()
                                ->title("Licence {$season->name} déjà existante pour ce joueur")
                                ->warning()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title("Licence {$season->name} créée")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
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
