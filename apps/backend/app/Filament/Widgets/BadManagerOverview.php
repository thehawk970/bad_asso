<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use App\Models\License;
use App\Models\Payment;
use App\Models\Player;
use App\Models\Season;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BadManagerOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $currentSeason = Season::current();

        // Licences non validées sur la saison en cours
        $pendingLicensesQuery = License::whereIn('status', [
            LicenseStatus::Pending->value,
            LicenseStatus::InProgress->value,
        ]);
        if ($currentSeason) {
            $pendingLicensesQuery->where('season_id', $currentSeason->id);
        }
        $pendingLicenses = $pendingLicensesQuery->count();

        // Paiements en attente
        $pendingPayments = Payment::where('status', PaymentStatus::Pending->value)->count();

        // Joueurs sans licence pour la saison active
        $playersWithoutCurrentLicense = $currentSeason
            ? Player::whereDoesntHave('licenses', function ($q) use ($currentSeason) {
                $q->where('season_id', $currentSeason->id);
            })->count()
            : Player::count();

        // Paiements validés
        $validatedPayments = Payment::where('status', PaymentStatus::Validated->value)->count();

        $seasonLabel = $currentSeason ? "Saison {$currentSeason->name}" : 'Aucune saison active';

        return [
            Stat::make('Licences non validées', $pendingLicenses)
                ->description($seasonLabel . ' — en attente ou en cours')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Paiements en attente', $pendingPayments)
                ->description('À valider')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Sans licence ' . ($currentSeason !== null ? $currentSeason->name : '—'), $playersWithoutCurrentLicense)
                ->description('Joueurs non encore renouvelés')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($playersWithoutCurrentLicense > 0 ? 'danger' : 'success'),

            Stat::make('Paiements validés', $validatedPayments)
                ->description('Total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
