<?php

declare(strict_types=1);

namespace App\Filament\Resources\LicenseResource\Pages;

use App\Filament\Resources\LicenseResource;
use App\Models\License;
use App\Services\LicenseService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLicense extends EditRecord
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var License $license */
        $license = $this->record->fresh();

        if (app(LicenseService::class)->updateConditionsAndValidate($license, [])) {
            Notification::make()
                ->title('Licence validée automatiquement')
                ->body('Toutes les conditions sont remplies.')
                ->success()
                ->send();
        }
    }
}
