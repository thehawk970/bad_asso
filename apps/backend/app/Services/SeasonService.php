<?php

namespace App\Services;

use App\Models\Season;

class SeasonService
{
    /**
     * Crée une nouvelle saison et l'active immédiatement.
     * Retourne null si une saison avec ce nom existe déjà.
     */
    public function createAndActivate(array $data): ?Season
    {
        if (Season::where('name', $data['name'])->exists()) {
            return null;
        }

        $season = Season::create([
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'is_active'  => false,
        ]);

        $season->activate();

        return $season;
    }
}
