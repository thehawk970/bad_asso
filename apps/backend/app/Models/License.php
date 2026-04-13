<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property LicenseStatus $status
 * @property bool $payment_confirmed
 * @property bool $health_form_filled
 * @property bool $info_form_filled
 * @property bool $rules_signed
 */
class License extends Model
{
    protected $fillable = [
        'player_id',
        'season_id',
        'status',
        'payment_confirmed',
        'health_form_filled',
        'info_form_filled',
        'rules_signed',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'status'             => LicenseStatus::class,
            'payment_confirmed'  => 'boolean',
            'health_form_filled' => 'boolean',
            'info_form_filled'   => 'boolean',
            'rules_signed'       => 'boolean',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    // ─── Conditions de validation ────────────────────────────────────────────────

    /**
     * Retourne les conditions manquantes pour valider la licence.
     * Tableau vide = tout est OK.
     *
     * @return string[]
     */
    public function missingConditions(): array
    {
        $missing = [];

        if (! $this->payment_confirmed)  $missing[] = 'Paiement non confirmé';
        if (! $this->health_form_filled) $missing[] = 'Formulaire de santé non rempli';
        if (! $this->info_form_filled)   $missing[] = 'Formulaire de renseignements non rempli';
        if (! $this->rules_signed)       $missing[] = 'Règlement Poona non signé';

        return $missing;
    }

    public function isReadyToValidate(): bool
    {
        return empty($this->missingConditions());
    }

    /**
     * Valide la licence si toutes les conditions sont remplies.
     * Retourne true si la licence a été validée.
     */
    public function checkAndValidate(): bool
    {
        if (! $this->isReadyToValidate()) {
            return false;
        }

        if ($this->status !== LicenseStatus::Validated) {
            $this->update(['status' => LicenseStatus::Validated]);
        }

        return true;
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * @param Builder<License> $query
     * @return Builder<License>
     */
    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', LicenseStatus::Validated);
    }

    /**
     * @param Builder<License> $query
     * @return Builder<License>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LicenseStatus::Pending);
    }

    /**
     * @param Builder<License> $query
     * @return Builder<License>
     */
    public function scopeForSeason(Builder $query, Season $season): Builder
    {
        return $query->where('season_id', $season->id);
    }
}
