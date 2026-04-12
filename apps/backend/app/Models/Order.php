<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['player_id', 'status', 'total', 'reference', 'paid_at'])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status'  => OrderStatus::class,
            'total'   => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Business logic ─────────────────────────────────────────────────────────

    /**
     * Recalcule et persiste le total depuis les lignes de commande.
     */
    public function recalculateTotal(): void
    {
        $total = $this->items()->sum(DB::raw('unit_price * quantity'));
        $this->update(['total' => $total]);
    }

    /**
     * Vérifie si les paiements validés couvrent le total de la commande.
     * Si oui, marque la commande comme payée et tente de valider la licence.
     *
     * @return bool  true si la commande vient d'être marquée payée
     */
    public function checkIfFullyPaid(): bool
    {
        if ($this->status === OrderStatus::Paid) {
            return false;
        }

        $totalPaid = $this->payments()
            ->where('status', PaymentStatus::Validated->value)
            ->sum('amount');

        if ($totalPaid < $this->total) {
            return false;
        }

        $this->update([
            'status'  => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->autoValidateLicense();

        return true;
    }

    /**
     * Raccourci : marque manuellement la commande comme payée
     * (sans créer de paiement — utiliser le RelationManager pour ça).
     *
     * @return bool  true si une licence a été validée automatiquement
     */
    public function markAsPaid(PaymentMethod $method): bool
    {
        $this->update([
            'status'  => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->update([
                'status' => PaymentStatus::Validated->value,
                'method' => $method->value,
            ]);

        return $this->autoValidateLicense();
    }

    /**
     * Montant déjà payé (paiements validés).
     */
    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', PaymentStatus::Validated->value)
            ->sum('amount');
    }

    /**
     * Montant restant à régler.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total - $this->amount_paid);
    }

    // ─── Licence ────────────────────────────────────────────────────────────────

    /**
     * Crée une licence en attente dès la création de la commande si elle contient
     * un produit licence. N'active pas payment_confirmed (pas encore payé).
     */
    public function autoCreatePendingLicense(): void
    {
        $hasLicenseProduct = $this->items()
            ->whereHas('product', fn (Builder $q) => $q->where('is_license_product', true))
            ->exists();

        if (! $hasLicenseProduct) {
            return;
        }

        $currentSeason = Season::current();

        $existing = $this->player->licenses()
            ->when(
                $currentSeason,
                fn (Builder $q) => $q->where('season_id', $currentSeason->id),
                fn (Builder $q) => $q->latest(),
            )
            ->whereIn('status', [LicenseStatus::Pending->value, LicenseStatus::InProgress->value])
            ->first();

        if (! $existing) {
            $this->player->licenses()->create([
                'season_id'         => $currentSeason?->id,
                'status'            => LicenseStatus::Pending,
                'payment_confirmed' => false,
            ]);
        }
    }

    /**
     * Marque le paiement comme confirmé sur la licence et tente de la valider.
     * Appelé quand la commande passe en "payée".
     *
     * @return bool  true si la licence vient d'être validée
     */
    private function autoValidateLicense(): bool
    {
        $hasLicenseProduct = $this->items()
            ->whereHas('product', fn (Builder $q) => $q->where('is_license_product', true))
            ->exists();

        if (! $hasLicenseProduct) {
            return false;
        }

        $currentSeason = Season::current();

        $license = $this->player->licenses()
            ->when(
                $currentSeason,
                fn (Builder $q) => $q->where('season_id', $currentSeason->id),
                fn (Builder $q) => $q->latest(),
            )
            ->whereIn('status', [LicenseStatus::Pending->value, LicenseStatus::InProgress->value])
            ->first();

        if (! $license) {
            $license = $this->player->licenses()->create([
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

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Pending);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Paid);
    }
}
