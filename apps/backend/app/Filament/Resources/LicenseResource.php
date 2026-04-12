<?php

namespace App\Filament\Resources;

use App\Enums\LicenseStatus;
use App\Filament\Resources\LicenseResource\Pages;
use App\Models\License;
use App\Models\Player;
use App\Models\Season;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Licences';

    protected static ?string $modelLabel = 'licence';

    protected static ?string $pluralModelLabel = 'licences';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('player_id')
                ->label('Joueur')
                ->getSearchResultsUsing(fn (string $search) => Player::where('last_name', 'ilike', "%{$search}%")
                    ->orWhere('first_name', 'ilike', "%{$search}%")
                    ->orderBy('last_name')
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (Player $p) => [$p->id => $p->last_name . ' ' . $p->first_name])
                )
                ->getOptionLabelUsing(fn ($value) => Player::find($value)?->full_name ?? '—')
                ->searchable()
                ->required(),

            Select::make('season_id')
                ->label('Saison')
                ->options(Season::orderBy('start_date', 'desc')->pluck('name', 'id'))
                ->required(),

            Select::make('status')
                ->label('Statut')
                ->options(collect(LicenseStatus::cases())->mapWithKeys(
                    fn (LicenseStatus $s) => [$s->value => $s->label()]
                ))
                ->required(),

            // ─── Conditions de validation ────────────────────────────────────
            Toggle::make('payment_confirmed')
                ->label('Paiement confirmé')
                ->helperText('Coché automatiquement lors du paiement d\'une commande avec produit licence.')
                ->inline(false),

            Toggle::make('health_form_filled')
                ->label('Formulaire de santé rempli')
                ->inline(false),

            Toggle::make('info_form_filled')
                ->label('Formulaire de renseignements rempli')
                ->inline(false),

            Toggle::make('rules_signed')
                ->label('Règlement Poona signé')
                ->inline(false),
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
                    ->formatStateUsing(fn ($state, License $record) => $record->player?->full_name ?? '—'),

                TextColumn::make('season.name')
                    ->label('Saison')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(fn (License $record) => $record->status->label())
                    ->color(fn (License $record) => $record->status->color()),

                // Conditions (icônes)
                IconColumn::make('payment_confirmed')
                    ->label('Paiement')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('health_form_filled')
                    ->label('Santé')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('info_form_filled')
                    ->label('Rens.')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('rules_signed')
                    ->label('Règlement')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('season')
                    ->label('Saison')
                    ->relationship('season', 'name'),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(LicenseStatus::cases())->mapWithKeys(
                        fn (LicenseStatus $s) => [$s->value => $s->label()]
                    )),
            ])
            ->actions([
                Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->hidden(fn (License $record) => $record->status === LicenseStatus::Validated)
                    ->modalHeading('Mise à jour de la licence')
                    ->modalDescription('Cochez les conditions remplies puis confirmez.')
                    ->form(fn (License $record) => [
                        Toggle::make('payment_confirmed')
                            ->label('Paiement confirmé')
                            ->inline(false)
                            ->default($record->payment_confirmed),

                        Toggle::make('health_form_filled')
                            ->label('Formulaire de santé rempli')
                            ->inline(false)
                            ->default($record->health_form_filled),

                        Toggle::make('info_form_filled')
                            ->label('Formulaire de renseignements rempli')
                            ->inline(false)
                            ->default($record->info_form_filled),

                        Toggle::make('rules_signed')
                            ->label('Règlement Poona signé')
                            ->inline(false)
                            ->default($record->rules_signed),
                    ])
                    ->action(function (License $record, array $data): void {
                        $record->update($data);
                        $record->refresh();

                        $validated = $record->checkAndValidate();

                        if ($validated) {
                            Notification::make()
                                ->title('Licence validée ✓')
                                ->success()
                                ->send();

                            return;
                        }

                        $missing = $record->missingConditions();

                        Notification::make()
                            ->title('Conditions mises à jour')
                            ->body(empty($missing)
                                ? 'Toutes les conditions sont remplies.'
                                : 'Conditions restantes : ' . implode(' · ', $missing)
                            )
                            ->info()
                            ->send();
                    }),

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
            'index'  => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            'edit'   => Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}
