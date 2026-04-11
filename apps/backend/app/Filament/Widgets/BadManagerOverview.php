<?php

namespace App\Filament\Widgets;

use App\Enums\LicenseStatus;
use App\Enums\PaymentStatus;
use App\Models\License;
use App\Models\Payment;
use App\Models\Player;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BadManagerOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingLicenses = License::where('status', LicenseStatus::Pending->value)
            ->orWhere('status', LicenseStatus::InProgress->value)
            ->count();

        $pendingPayments = Payment::where('status', PaymentStatus::Pending->value)->count();

        $playersWithoutLicense = Player::whereDoesntHave('licenses')->count();

        $validatedPayments = Payment::where('status', PaymentStatus::Validated->value)->count();

        return [
            Stat::make('Licences non validées', $pendingLicenses)
                ->description('En attente ou en cours')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->url(route('filament.admin.resources.licenses.index', ['tableFilters[status][value]' => LicenseStatus::Pending->value])),

            Stat::make('Paiements en attente', $pendingPayments)
                ->description('À valider')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.payments.index', ['tableFilters[status][value]' => PaymentStatus::Pending->value])),

            Stat::make('Joueurs sans licence', $playersWithoutLicense)
                ->description('Aucune licence enregistrée')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('info')
                ->url(route('filament.admin.resources.players.index', ['tableFilters[without_license][isActive]' => true])),

            Stat::make('Paiements validés', $validatedPayments)
                ->description('Cette saison')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.admin.resources.payments.index', ['tableFilters[status][value]' => PaymentStatus::Validated->value])),
        ];
    }
}
