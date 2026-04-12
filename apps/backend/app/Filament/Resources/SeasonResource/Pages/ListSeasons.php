<?php

namespace App\Filament\Resources\SeasonResource\Pages;

use App\Filament\Resources\SeasonResource;
use App\Models\Season;
use App\Services\SeasonService;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSeasons extends ListRecords
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('next_season')
                ->label('Nouvelle saison')
                ->icon('heroicon-o-forward')
                ->color('warning')
                ->fillForm(function (): array {
                    $current = Season::orderBy('end_date', 'desc')->first();

                    if ($current) {
                        // Calcul automatique : +1 an
                        $nextStart = Carbon::parse($current->end_date)->addDay(); // 01/09
                        $nextEnd   = $nextStart->copy()->addYear()->subDay();     // 31/08

                        // Nom : "25-26" -> "26-27"
                        [$y1, $y2] = explode('-', $current->name);
                        $nextName  = ((int) $y2) . '-' . ((int) $y2 + 1);
                    } else {
                        $nextStart = Carbon::createFromDate(null, 9, 1);
                        $nextEnd   = $nextStart->copy()->addYear()->subDay();
                        $nextName  = $nextStart->format('y') . '-' . $nextEnd->format('y');
                    }

                    return [
                        'name'       => $nextName,
                        'start_date' => $nextStart->toDateString(),
                        'end_date'   => $nextEnd->toDateString(),
                    ];
                })
                ->form([
                    TextInput::make('name')
                        ->label('Nom de la saison (ex: 26-27)')
                        ->required()
                        ->maxLength(10),

                    DatePicker::make('start_date')
                        ->label('Début')
                        ->required()
                        ->displayFormat('d/m/Y'),

                    DatePicker::make('end_date')
                        ->label('Fin')
                        ->required()
                        ->displayFormat('d/m/Y'),
                ])
                ->action(function (array $data): void {
                    $season = app(SeasonService::class)->createAndActivate($data);

                    if (! $season) {
                        Notification::make()
                            ->title("La saison {$data['name']} existe déjà")
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title("Saison {$season->name} créée et activée")
                        ->body('Les joueurs sans licence pour cette saison apparaissent maintenant dans les alertes.')
                        ->success()
                        ->send();
                }),

            CreateAction::make()->label('Créer manuellement'),
        ];
    }
}
