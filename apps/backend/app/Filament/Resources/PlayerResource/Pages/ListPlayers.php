<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use App\Services\PlayerImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Importer FFBad')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->schema([
                    FileUpload::make('csv_file')
                        ->label('Fichier CSV FFBad')
                        ->disk('local')
                        ->directory('player-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/octet-stream'])
                        ->required()
                        ->helperText('Export CSV de Poona/FFBad, séparateur point-virgule.'),
                ])
                ->action(function (array $data, PlayerImportService $service): void {
                    $path = $data['csv_file'];

                    if (! is_string($path)) {
                        Notification::make()->title('Fichier invalide')->danger()->send();

                        return;
                    }

                    $fullPath = Storage::disk('local')->path($path);

                    $result = $service->importFromPath($fullPath);

                    $body = "{$result['created']} créé(s), {$result['updated']} mis à jour, {$result['skipped']} ignoré(s).";

                    if ($result['errors'] !== []) {
                        $body .= "\n".implode("\n", $result['errors']);
                    }

                    if ($result['errors'] !== []) {
                        Notification::make()
                            ->title('Import terminé avec des avertissements')
                            ->body($body)
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import réussi')
                            ->body($body)
                            ->success()
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}
