<?php

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Enums\LicenseStatus;
use App\Filament\Resources\PlayerResource;
use App\Models\Season;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayer extends CreateRecord
{
    protected static string $resource = PlayerResource::class;

    protected function afterCreate(): void
    {
        $season = Season::current();

        if (! $season) {
            Notification::make()
                ->title('Aucune saison active')
                ->body('Le joueur a été créé mais aucune licence n\'a pu être générée. Activez une saison d\'abord.')
                ->warning()
                ->send();

            return;
        }

        $this->record->licenses()->create([
            'season_id' => $season->id,
            'status'    => LicenseStatus::Pending,
        ]);

        Notification::make()
            ->title("Licence {$season->name} créée automatiquement")
            ->success()
            ->send();
    }
}
