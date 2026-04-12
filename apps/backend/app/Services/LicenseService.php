<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Order;
use App\Models\Player;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;

class LicenseService
{
    /**
     * Crée une licence en attente pour le joueur de la commande
     * si la commande contient un produit licence et qu'aucune licence active n'existe.
     */
    public function ensurePendingLicenseForOrder(Order $order): void
    {
        if (! $this->orderHasLicenseProduct($order)) {
            return;
        }

        $player = $order->player;
        if ($player === null) {
            return;
        }

        $currentSeason = Season::current();

        $seasonId = $currentSeason?->id;
        $exists = $player->licenses()
            ->when(
                $seasonId !== null,
                fn (Builder $q) => $q->where('season_id', $seasonId),
                fn (Builder $q) => $q->latest(),
            )
            ->whereIn('status', [LicenseStatus::Pending->value, LicenseStatus::InProgress->value])
            ->exists();

        if (! $exists) {
            $player->licenses()->create([
                'season_id'         => $currentSeason?->id,
                'status'            => LicenseStatus::Pending,
                'payment_confirmed' => false,
            ]);
        }
    }

    /**
     * Confirme le paiement sur la licence du joueur et tente de la valider.
     * Appelé lorsque la commande passe en statut "payée".
     *
     * @return bool  true si la licence vient d'être validée
     */
    public function confirmPaymentForOrder(Order $order): bool
    {
        if (! $this->orderHasLicenseProduct($order)) {
            return false;
        }

        $player = $order->player;
        if ($player === null) {
            return false;
        }

        $currentSeason = Season::current();
        $seasonId = $currentSeason?->id;

        $license = $player->licenses()
            ->when(
                $seasonId !== null,
                fn (Builder $q) => $q->where('season_id', $seasonId),
                fn (Builder $q) => $q->latest(),
            )
            ->whereIn('status', [LicenseStatus::Pending->value, LicenseStatus::InProgress->value])
            ->first();

        if (! $license) {
            $license = $player->licenses()->create([
                'season_id'         => $currentSeason?->id,
                'status'            => LicenseStatus::InProgress,
                'payment_confirmed' => true,
            ]);
        } else {
            $license->update([
                'payment_confirmed' => true,
                'status'            => LicenseStatus::InProgress,
            ]);
        }

        return $license->checkAndValidate();
    }

    /**
     * Crée une licence en attente pour un joueur sur une saison donnée.
     */
    public function createPendingForPlayer(Player $player, Season $season): License
    {
        return $player->licenses()->create([
            'season_id' => $season->id,
            'status'    => LicenseStatus::Pending,
        ]);
    }

    /**
     * Renouvelle la licence d'un joueur pour une saison.
     * Retourne null si une licence existe déjà pour cette saison.
     */
    public function renewForPlayer(Player $player, Season $season): ?License
    {
        if ($player->licenses()->where('season_id', $season->id)->exists()) {
            return null;
        }

        return $this->createPendingForPlayer($player, $season);
    }

    /**
     * Met à jour les conditions d'une licence et tente de la valider.
     *
     * @param  array{payment_confirmed?: bool, health_form_filled?: bool, info_form_filled?: bool, rules_signed?: bool}  $conditions
     * @return bool  true si la licence vient d'être validée
     */
    public function updateConditionsAndValidate(License $license, array $conditions): bool
    {
        if (! empty($conditions)) {
            $license->update($conditions);
            $license->refresh();
        }

        return $license->checkAndValidate();
    }

    private function orderHasLicenseProduct(Order $order): bool
    {
        return $order->items()
            ->whereHas('product', fn (Builder $q) => $q->where('is_license_product', true))
            ->exists();
    }
}
