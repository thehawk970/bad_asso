<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SeasonResource\Pages;
use App\Models\Season;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Saisons';

    protected static ?string $modelLabel = 'saison';

    protected static ?string $pluralModelLabel = 'saisons';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom (ex: 25-26)')
                ->required()
                ->maxLength(10)
                ->unique(ignoreRecord: true)
                ->placeholder('25-26'),

            DatePicker::make('start_date')
                ->label('Début de saison')
                ->required()
                ->displayFormat('d/m/Y'),

            DatePicker::make('end_date')
                ->label('Fin de saison')
                ->required()
                ->displayFormat('d/m/Y')
                ->after('start_date'),

            Toggle::make('is_active')
                ->label('Saison en cours')
                ->helperText('Activer désactivera automatiquement les autres saisons.')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Saison')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('licenses_count')
                    ->label('Licences')
                    ->counts('licenses')
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_active')
                    ->label('En cours')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Activer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (Season $record) => $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Activer cette saison ?')
                    ->modalDescription('La saison en cours sera désactivée.')
                    ->action(function (Season $record) {
                        $record->activate();
                        Notification::make()
                            ->title("Saison {$record->name} activée")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeasons::route('/'),
            'create' => Pages\CreateSeason::route('/create'),
            'edit'   => Pages\EditSeason::route('/{record}/edit'),
        ];
    }
}
