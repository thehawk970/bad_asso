<?php

declare(strict_types=1);

namespace App\Filament\Resources\SeasonResource\Pages;

use App\Filament\Resources\SeasonResource;
use App\Models\Season;
use Filament\Resources\Pages\CreateRecord;

class CreateSeason extends CreateRecord
{
    protected static string $resource = SeasonResource::class;

    protected function afterCreate(): void
    {
        /** @var Season $season */
        $season = $this->record;

        // Si la saison créée est marquée active, on désactive les autres
        if ($season->is_active) {
            $season->activate();
        }
    }
}
