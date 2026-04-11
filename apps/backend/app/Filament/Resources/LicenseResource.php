<?php

namespace App\Filament\Resources;

use App\Enums\LicenseStatus;
use App\Filament\Resources\LicenseResource\Pages;
use App\Models\License;
use App\Models\Season;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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
                ->relationship('player', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->last_name . ' ' . $record->first_name)
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
                    ->formatStateUsing(fn ($state, License $record) => $record->player->last_name . ' ' . $record->player->first_name),

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

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
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
