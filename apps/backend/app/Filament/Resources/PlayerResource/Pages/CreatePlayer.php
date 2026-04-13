<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlayerResource\Pages;

use App\Filament\Resources\PlayerResource;
use App\Models\Player;
use App\Models\Season;
use App\Services\LicenseService;
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

        /** @var Player $player */
        $player = $this->record;
        app(LicenseService::class)->createPendingForPlayer($player, $season);

        Notification::make()
            ->title("Licence {$season->name} créée automatiquement")
            ->success()
            ->send();
    }
}
