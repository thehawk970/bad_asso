<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LicenseStatus;
use App\Models\License;

class ValidateLicense
{
    /**
     * @throws \RuntimeException si les conditions ne sont pas toutes remplies
     */
    public function execute(License $license): License
    {
        $missing = $license->missingConditions();

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Impossible de valider la licence. Conditions manquantes : '.implode(', ', $missing)
            );
        }

        $license->update(['status' => LicenseStatus::Validated]);
        $license->refresh();

        return $license;
    }
}
