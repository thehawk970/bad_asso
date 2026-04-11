<?php

namespace App\Actions;

use App\Enums\LicenseStatus;
use App\Models\License;

class ValidateLicense
{
    public function execute(License $license): License
    {
        $license->update(['status' => LicenseStatus::Validated]);

        return $license->fresh();
    }
}
