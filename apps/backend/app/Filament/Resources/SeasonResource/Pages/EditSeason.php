<?php

namespace App\Filament\Resources\SeasonResource\Pages;

use App\Filament\Resources\SeasonResource;
use App\Models\Season;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeason extends EditRecord
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (Season $record) => $record->licenses()->exists()),
        ];
    }

    protected function afterSave(): void
    {
        /** @var Season $season */
        $season = $this->record;

        if ($season->is_active) {
            $season->activate();
        }
    }
}
