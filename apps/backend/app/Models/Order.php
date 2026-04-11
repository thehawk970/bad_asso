<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['player_id', 'status', 'total', 'payment_method', 'reference', 'paid_at'])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status'         => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'total'          => 'decimal:2',
            'paid_at'        => 'datetime',
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
     * Marque la commande comme payée et valide automatiquement la licence
     * du joueur si la commande contient un produit de type licence.
     *
     * @return bool  true si une licence a été validée automatiquement
     */
    public function markAsPaid(?PaymentMethod $method = null, ?string $reference = null): bool
    {
        $this->update([
            'status'         => OrderStatus::Paid,
            'payment_method' => $method ?? $this->payment_method,
            'reference'      => $reference ?? $this->reference,
            'paid_at'        => now(),
        ]);

        return $this->autoValidateLicense();
    }

    /**
     * Si la commande contient un produit licence, valide la licence active
     * du joueur pour la saison en cours.
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
            return false;
        }

        $license->update(['status' => LicenseStatus::Validated]);

        return true;
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
